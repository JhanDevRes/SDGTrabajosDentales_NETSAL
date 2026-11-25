{{--
    Correo de aviso al laboratorio por una solicitud del sitio web.
    Se usan estilos en línea porque los clientes de correo no cargan hojas externas.
--}}
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva solicitud de contacto</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7fa;font-family:'Segoe UI',Arial,sans-serif;color:#16202b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #d9e1ea;border-radius:6px;">
        <tr>
            <td style="padding:20px 24px;border-bottom:1px solid #d9e1ea;">
                <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#667388;">NETSAL · Laboratorio Dental Digital</div>
                <h1 style="margin:6px 0 0;font-size:18px;color:#102a43;">Nueva solicitud de contacto</h1>
                <div style="margin-top:2px;font-size:13px;color:#667388;">Referencia SC-{{ str_pad((string) $solicitud->id, 4, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="padding:6px 0;color:#667388;width:130px;">Nombre</td>
                        <td style="padding:6px 0;font-weight:600;">{{ $solicitud->nombre }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#667388;">Teléfono</td>
                        <td style="padding:6px 0;">{{ $solicitud->telefono }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#667388;">Correo</td>
                        <td style="padding:6px 0;">{{ $solicitud->email ?: 'No indicado' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#667388;">Trabajo solicitado</td>
                        <td style="padding:6px 0;">{{ $solicitud->servicio ?: 'No especificado' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#667388;">Recibida</td>
                        <td style="padding:6px 0;">{{ $solicitud->created_at->timezone(config('sitio.zona_horaria'))->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>

                <div style="margin-top:16px;padding:14px 16px;background:#f5f7fa;border-left:3px solid #1b65a6;border-radius:4px;">
                    <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#667388;margin-bottom:6px;">Detalle del caso</div>
                    <div style="font-size:14px;line-height:1.55;white-space:pre-line;">{{ $solicitud->mensaje }}</div>
                </div>

                <p style="margin:20px 0 0;font-size:13px;color:#667388;">
                    La solicitud ya está registrada en el sistema, en la bandeja «Solicitudes».
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
