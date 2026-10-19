<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #333; max-width: 600px; margin: 0 auto;">

    <div style="background: #FDE8E8; border-left: 4px solid #DC2626; padding: 12px 16px; margin-bottom: 20px;">
        <strong style="color: #DC2626;">Contenido prohibido detectado</strong><br>
        Se ha bloqueado un intento de subir contenido no permitido.
        @if($ipBanned)
            <br>La IP ha sido baneada automáticamente por {{ $banHours }} horas.
        @endif
    </div>

    <table style="border-collapse: collapse; width: 100%; margin-bottom: 16px;">
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold; width: 160px;">IP</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;"><code>{{ $ipAddress }}</code></td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Email</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ $email ?? 'No proporcionado' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">User Agent</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-size: 12px; word-break: break-all;">{{ $userAgent ?? 'Desconocido' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Tipo violación</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;">
                <span style="background: #DC2626; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">{{ strtoupper($violationType) }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Score confianza</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ $violationScore !== null ? number_format($violationScore * 100, 1) . '%' : 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Provider detección</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;"><code>{{ $provider }}</code></td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Hash archivo (SHA-256)</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-size: 11px; font-family: monospace; word-break: break-all;">{{ $fileHash }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;">Fecha (Madrid)</td>
            <td style="padding: 8px 12px; border: 1px solid #ddd;">{{ now()->timezone('Europe/Madrid')->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>

    <p style="background: #F5F5F5; padding: 10px 14px; font-size: 12px; color: #666; border-radius: 4px;">
        La imagen ha sido eliminada inmediatamente. Solo se conserva el hash SHA-256 como evidencia.
    </p>

    <p style="font-size: 12px; color: #999; margin-top: 20px;">
        — GenPic Admin ·
        <a href="{{ url(config('genpic.admin_path', 'admin') . '/content-violations') }}" style="color: #7A2D3B;">Ver violaciones</a> ·
        <a href="{{ url(config('genpic.admin_path', 'admin') . '/ip-bans') }}" style="color: #7A2D3B;">Ver bans</a>
    </p>
</div>
