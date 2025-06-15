<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            /* La original era noto sans */
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 100px 30px 90px 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid black;
        }

        th,
        td {
            border: 1px solid black;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }

        .header,
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            text-align: center;
        }

        .header img,
        .footer img {
            width: 110%;
        }

        .header {
            top: -50;
        }

        .footer {
            bottom: -50;
        }

        .header-text {
            text-align: center;
            font-weight: bold;
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="{{ public_path('images/encabezado-web.png') }}">
    </div>

    <div class="footer">
        <img src="{{ public_path('images/pie-web.png') }}">
    </div>

    <h2 class="header-text">Reporte de actividades Quincenal</h2>
    <p class="header-text">16 al 30 de abril del 2025</p>
    <table>
        <thead>
            <tr>
                <th>Gerencia</th>
                <th>Tipo de actividad</th>
                <th>Descripción de la actividad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($actividades as $actividad)
                <tr>
                    <td>{{ $actividad->pertenencia_nombre }}</td>
                    <td>{{ $actividad->tipo_actividad_id == 1 ? 'Sustantiva' : 'Cotidiana' }}</td>
                    <td>
                        <strong>{{ $actividad->titulo }}</strong><br>
                        {{ $actividad->descripcion }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>