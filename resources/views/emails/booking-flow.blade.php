<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>{{ $payload['subject'] }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef4f8;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#3a444e;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $payload['intro'] }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef4f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:#ffffff;border-radius:22px;overflow:hidden;box-shadow:0 18px 40px -24px rgba(11,58,84,0.45);">
                    <!-- Encabezado -->
                    <tr>
                        <td style="background-color:#0b6b9b;background-image:linear-gradient(135deg,#0b6b9b 0%,#1585bd 45%,#2f8f37 100%);padding:30px 32px 26px 32px;">
                            <p style="margin:0;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#d9edf8;font-weight:600;">
                                {{ $payload['eyebrow'] }}
                            </p>
                            <p style="margin:10px 0 0 0;font-size:26px;line-height:1.2;color:#ffffff;font-weight:700;letter-spacing:-0.4px;">
                                WashApp
                            </p>
                            <p style="margin:4px 0 0 0;font-size:12px;color:#d9edf8;">
                                Eco Car Wash Móvil · Cuidamos tu auto, cuidamos el agua
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido -->
                    <tr>
                        <td style="padding:32px 32px 8px 32px;">
                            <h1 style="margin:0;font-size:21px;line-height:1.3;color:#2f3d47;font-weight:700;">
                                {{ $payload['title'] }}
                            </h1>
                            <p style="margin:12px 0 0 0;font-size:14px;line-height:1.65;color:#667d90;">
                                {{ $payload['intro'] }}
                            </p>
                        </td>
                    </tr>

                    @if (! empty($payload['rows']))
                        <tr>
                            <td style="padding:22px 32px 0 32px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f7fafc;border:1px solid #e3edf4;border-radius:16px;">
                                    @foreach ($payload['rows'] as $row)
                                        <tr>
                                            <td style="padding:11px 16px;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:#8098ac;font-weight:600;white-space:nowrap;{{ $loop->last ? '' : 'border-bottom:1px solid #e8f0f6;' }}">
                                                {{ $row['label'] }}
                                            </td>
                                            <td align="right" style="padding:11px 16px;font-size:13px;color:#3a444e;font-weight:600;{{ $loop->last ? '' : 'border-bottom:1px solid #e8f0f6;' }}">
                                                {{ $row['value'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (! empty($payload['highlight']))
                        @php
                            $tone = $payload['highlight']['tone'] ?? 'cloud';
                            $highlightStyles = [
                                'cloud' => ['bg' => '#eef7fc', 'border' => '#b3dcf1', 'title' => '#0a587f', 'text' => '#45515d'],
                                'sage' => ['bg' => '#f1faf2', 'border' => '#bbe7bf', 'title' => '#245b2a', 'text' => '#45515d'],
                                'sand' => ['bg' => '#faf8f5', 'border' => '#e6ded1', 'title' => '#8c7a62', 'text' => '#45515d'],
                            ];
                            $style = $highlightStyles[$tone] ?? $highlightStyles['cloud'];
                        @endphp
                        <tr>
                            <td style="padding:20px 32px 0 32px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $style['bg'] }};border:1px solid {{ $style['border'] }};border-radius:16px;">
                                    <tr>
                                        <td style="padding:16px 18px;">
                                            <p style="margin:0;font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:{{ $style['title'] }};font-weight:700;">
                                                {{ $payload['highlight']['title'] }}
                                            </p>
                                            @foreach ($payload['highlight']['lines'] as $line)
                                                <p style="margin:8px 0 0 0;font-size:14px;line-height:1.5;color:{{ $style['text'] }};">
                                                    {{ $line }}
                                                </p>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (! empty($payload['cta']))
                        <tr>
                            <td align="center" style="padding:26px 32px 6px 32px;">
                                <a href="{{ $payload['cta']['url'] }}"
                                   style="display:inline-block;background-color:#0b6b9b;background-image:linear-gradient(135deg,#0b6b9b,#1585bd);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:14px 26px;border-radius:14px;">
                                    {{ $payload['cta']['label'] }}
                                </a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:26px 32px 0 32px;">
                            <div style="height:1px;background-color:#e8f0f6;"></div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px 30px 32px;">
                            <p style="margin:0;font-size:11px;line-height:1.6;color:#a3b8c8;">
                                {{ $payload['footer'] ?? 'Este correo se generó automáticamente por tu reserva en WashApp.' }}
                            </p>
                            <p style="margin:8px 0 0 0;font-size:11px;line-height:1.6;color:#a3b8c8;">
                                WashApp · notificaciones@dtw.com.mx
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0 0;font-size:10px;color:#a3b8c8;text-align:center;">
                    © {{ date('Y') }} WashApp. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
