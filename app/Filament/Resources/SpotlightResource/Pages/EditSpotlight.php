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
        
        // If we have a record and a selected category, let's add the dynamic attribute fields
        if ($this->record && $this->record->category_id) {
            $this->addAttributeFields($form);
        }
        
        return $form;
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
            $currentValue = $this->record->getAttributesByDefinition($definition->id)->first();
            
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
                        ->default($this->record->getAttributesByDefinition($definition->id)->pluck('option_id')->toArray());
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
        
        // Add fields to custom attributes tab
        $form->getComponents()[0]->getChildComponents()[0]->getChildComponents()[5]->schema([
            Forms\Components\Section::make('Category-specific Attributes')
                ->schema($attributeFields)
                ->columns(2),
        ]);
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
