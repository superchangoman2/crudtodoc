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
            vertical-align: middle;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 14px;
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

        .backimg {
            opacity: 0.5;
        }
    </style>
</head>

<body>

    <div class="header">
        <img class="backimg" src="{{ public_path('images/encabezado-web.png') }}">
    </div>

    <div class="footer">
        <img class="backimg" src="{{ public_path('images/pie-web.png') }}">
    </div>

    <h2 class="header-text">{{ $titulo }}</h2>
    @if ($rangoFechas)
        <p class="header-text">{{ $rangoFechas }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>{{ optional($actividades->first())->pertenencia_tipo ?? 'Pertenencia' }}</th>
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
                    {{ $actividad->fecha }}
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>