<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #333; line-height: 1.6; padding: 20px;">

@if($allFailed)
<div style="background: #FDE8E8; border-left: 4px solid #DC2626; padding: 12px 16px; margin-bottom: 20px; border-radius: 0 4px 4px 0;">
    <strong style="color: #DC2626;">TODOS los proveedores IA han fallado.</strong><br>
    Las generaciones de usuarios no se pueden completar hasta que al menos uno se recupere.
</div>
@endif

<h2 style="margin: 0 0 16px 0; color: #1a1a1a;">
    Proveedor IA fallido: {{ $providerName }}
</h2>

<table style="border-collapse: collapse; width: 100%; max-width: 500px; margin-bottom: 20px;">
    <tr>
        <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: 600; width: 130px;">Proveedor</td>
        <td style="padding: 8px 12px; border: 1px solid #ddd;"><code>{{ $providerName }}</code></td>
    </tr>
    <tr>
        <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: 600;">Tipo de error</td>
        <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ $isTimeout ? 'Timeout' : 'Error' }}</td>
    </tr>
    <tr>
        <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: 600;">Cooldown</td>
        <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ $cooldownSeconds }}s</td>
    </tr>
    <tr>
        <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: 600;">Fecha/hora</td>
        <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ now()->timezone('Europe/Madrid')->format('d/m/Y H:i:s') }}</td>
    </tr>
</table>

<div style="background: #F5F5F5; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">
    <strong>Mensaje de error:</strong><br>
    <code style="font-size: 13px; word-break: break-all;">{{ $errorMessage }}</code>
</div>

<p style="color: #666; font-size: 12px;">
    Este email se envía automáticamente desde GenPic.com cuando un proveedor IA falla.
    Revisa los logs en <a href="{{ config('app.url') }}/admin/provider-logs">Filament &rarr; Provider Logs</a>.
</p>

</body>
</html>
