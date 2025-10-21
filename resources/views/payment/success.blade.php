<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Successful</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; padding: 0; background:#f6f8fa; color:#0f172a; }
    .container { max-width: 560px; margin: 10vh auto; background:#fff; border-radius: 12px; box-shadow: 0 6px 24px rgba(15,23,42,0.08); padding: 28px; }
    .status { display:flex; align-items:center; gap:12px; font-size: 20px; font-weight: 700; color: #16a34a; }
    .status .icon { width: 28px; height: 28px; }
    .meta { margin-top: 10px; color:#475569; font-size:14px; }
    .actions { margin-top: 24px; display:flex; gap: 12px; flex-wrap:wrap; }
    .btn { display:inline-block; padding:10px 14px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; }
    .btn.primary { background:#0ea5e9; color:white; }
    .btn.secondary { background:#e2e8f0; color:#0f172a; }
  </style>
</head>
<body>
  <div class="container">
    <div class="status">
      <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12l2 2 4-4" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="#16a34a" stroke-width="2"/></svg>
      <span>Payment Successful</span>
    </div>
    <div class="meta">
      @if(!empty($booking_number))
        Booking Number: <strong>{{ $booking_number }}</strong>
      @endif
    </div>
    <div class="actions">
      <a class="btn primary" href="/">Go to Home</a>
      <a class="btn secondary" href="javascript:history.back()">Go Back</a>
    </div>
  </div>
</body>
</html>
