<?php

namespace App\Filament\Resources\SpotlightResource\Pages;

use App\Filament\Resources\SpotlightResource;
use App\Models\SpotlightAttributeDefinition;
use App\Models\SpotlightAttributeValue;
use App\Models\SpotlightCategoryAttribute;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;

class EditSpotlight extends EditRecord
{
    protected static string $resource = SpotlightResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // Comment out until frontend routes are implemented
            // Actions\Action::make('view')
            //     ->label('View Spotlight')
            //     ->url(fn ($record) => route('spotlights.show', $record->slug))
            //     ->icon('heroicon-o-eye')
            //     ->color('success')
            //     ->visible(fn ($record) => $record->is_published),
        ];
    }
    
    public function form(Form $form): Form
    {
        $form = parent::form($form);
        
        // We now handle dynamic attribute fields in the main resource form
        // No need to add them here anymore
        
        return $form;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store send_notification preference in session for observer
        if (isset($data['send_notification'])) {
            session(['spotlight_send_notification' => $data['send_notification']]);
            unset($data['send_notification']); // Remove from data since it's not a database field
        }
        
        // Handle video file upload
        if (isset($data['video_provider']) && $data['video_provider'] === 'self' && isset($data['video_file'])) {
            // Get the file path from Filament's temporary upload
            $filePath = $data['video_file'];
            
            // Set the video URL to the storage path
            $data['video_url'] = asset('storage/' . $filePath);
            
            // Log for debugging
            \Illuminate\Support\Facades\Log::debug('Video file upload in Filament', [
                'video_file' => $filePath,
                'video_url' => $data['video_url']
            ]);
        }
        
        return $data;
    }
    
    protected function addAttributeFields(Form $form): void
    {
        // Get category attribute definitions
        $categoryAttributes = SpotlightCategoryAttribute::where('category_id', $this->record->category_id)
            ->with('attributeDefinition.options')
            ->get();
            
        if ($categoryAttributes->isEmpty()) {
            return;
        }
        
        // Generate form fields for each attribute
        $attributeFields = $categoryAttributes->map(function ($categoryAttribute) {
            $definition = $categoryAttribute->attributeDefinition;
            $fieldType = $definition->getFormFieldType();
            $isRequired = $categoryAttribute->isRequired();
            
            // Get current value if it exists
            $attributesByDef = $this->record->getAttributesByDefinition();
            $currentValue = isset($attributesByDef[$definition->id]) && !empty($attributesByDef[$definition->id]['values']) 
                ? (is_object($attributesByDef[$definition->id]['values'][0]) 
                    ? $attributesByDef[$definition->id]['values'][0] 
                    : (object)['value' => $attributesByDef[$definition->id]['values'][0]]) 
                : null;
            
            $field = null;
            
            // Create appropriate field type based on definition
            switch($fieldType) {
                case 'text':
                    $field = Forms\Components\TextInput::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                    
                case 'textarea':
                    $field = Forms\Components\Textarea::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                    
                case 'number':
                    $field = Forms\Components\TextInput::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->numeric()
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                    
                case 'boolean':
                case 'toggle':
                    $field = Forms\Components\Toggle::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? false);
                    break;
                    
                case 'select':
                    $options = $definition->options->pluck('display_label', 'id')->toArray();
                    $field = Forms\Components\Select::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->options($options)
                        ->required($isRequired)
                        ->default($currentValue?->option_id ?? null);
                    break;
                    
                case 'multiselect':
                    $options = $definition->options->pluck('display_label', 'id')->toArray();
                    $field = Forms\Components\Select::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->options($options)
                        ->multiple()
                        ->required($isRequired)
                        ->default(function () use ($definition) {
                            $attributesByDef = $this->record->getAttributesByDefinition();
                            if (!isset($attributesByDef[$definition->id]) || empty($attributesByDef[$definition->id]['values'])) {
                                return [];
                            }
                            
                            // Extract option IDs from attribute values
                            $optionIds = [];
                            foreach ($attributesByDef[$definition->id]['values'] as $value) {
                                if (is_object($value) && isset($value->id)) {
                                    $optionIds[] = $value->id;
                                }
                            }
                            return $optionIds;
                        });
                    break;
                    
                case 'date':
                    $field = Forms\Components\DatePicker::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                    
                case 'time':
                    $field = Forms\Components\TimePicker::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                    
                case 'datetime':
                    $field = Forms\Components\DateTimePicker::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
                    break;
                
                default:
                    $field = Forms\Components\TextInput::make("attributes.{$definition->id}")
                        ->label($definition->name)
                        ->helperText($definition->description)
                        ->required($isRequired)
                        ->default($currentValue?->value ?? null);
            }
            
            return $field;
        })->filter()->toArray();
        
        // Find the Custom Attributes tab and add fields to it
        $tabs = $form->getComponents()[0]->getChildComponents()[0]->getChildComponents();
        
        // Find the Custom Attributes tab by its label
        $customAttributesTab = null;
        foreach ($tabs as $tab) {
            if ($tab instanceof Forms\Components\Tabs\Tab && $tab->getLabel() === 'Custom Attributes') {
                $customAttributesTab = $tab;
                break;
            }
        }
        
        // If we found the tab, add our fields to it
        if ($customAttributesTab) {
            $customAttributesTab->schema([
                Forms\Components\Section::make('Category-specific Attributes')
                    ->schema($attributeFields)
                    ->columns(2),
            ]);
        }
    }
    
    protected function afterSave(): void
    {
        $this->saveAttributeValues();
    }
    
    protected function saveAttributeValues(): void
    {
        $formData = $this->form->getState();
        
        if (isset($formData['attributes']) && is_array($formData['attributes'])) {
            foreach ($formData['attributes'] as $definitionId => $value) {
                $definition = SpotlightAttributeDefinition::find($definitionId);
                
                if (!$definition) continue;
                
                // Delete existing values for this definition
                SpotlightAttributeValue::where('spotlight_id', $this->record->id)
                    ->where('attribute_definition_id', $definitionId)
                    ->delete();
                
                // Save new value
                if ($value !== null) {
                    if ($definition->type === 'enum') {
                        if (is_array($value)) {
                            // Handle multiple values for enum
                            foreach ($value as $optionId) {
                                SpotlightAttributeValue::create([
                                    'spotlight_id' => $this->record->id,
                                    'attribute_definition_id' => $definitionId,
                                    'attribute_option_id' => $optionId,
                                ]);
                            }
                        } else {
                            SpotlightAttributeValue::create([
                                'spotlight_id' => $this->record->id,
                                'attribute_definition_id' => $definitionId,
                                'attribute_option_id' => $value,
                            ]);
                        }
                    } else {
                        // Handle non-enum types
                        SpotlightAttributeValue::create([
                            'spotlight_id' => $this->record->id,
                            'attribute_definition_id' => $definitionId,
                            'value' => (string) $value,
                        ]);
                    }
                }
            }
        }
    }
}
