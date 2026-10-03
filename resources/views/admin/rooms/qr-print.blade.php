<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $room->room_name }} - RoomLink QR
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            color: #183042;
        }

        .print-container {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;
        }

        .qr-card {
            width: 7.5in;
            min-height: 9.5in;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            text-align: center;

            border: 2px solid #183042;
            padding: 50px;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;

            margin-bottom: 35px;
        }

        .room-label {
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;

            margin-bottom: 8px;
        }

        .room-name {
            font-size: 42px;
            font-weight: 800;

            margin-bottom: 35px;
        }

        .qr-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 35px;
        }

        .qr-wrapper svg {
            width: 420px;
            height: 420px;
        }

        .location {
            margin-bottom: 25px;
        }

        .building {
            font-size: 22px;
            font-weight: 700;

            margin-bottom: 8px;
        }

        .floor {
            font-size: 18px;
            color: #526773;
        }

        .instruction {
            margin-top: 10px;

            font-size: 14px;
            color: #687982;
        }

        .qr-code-id {
            margin-top: 12px;

            font-family: monospace;
            font-size: 12px;
            color: #8b989f;
        }

        .print-button {
            position: fixed;

            top: 20px;
            right: 20px;

            padding: 12px 20px;

            border: none;
            border-radius: 7px;

            background: #486D87;
            color: white;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;
        }

        .print-button:hover {
            background: #36566d;
        }


        @media print {

            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                background: white;
            }

            .print-container {
                min-height: 100vh;
                padding: 0;
            }

            .qr-card {
                width: 100%;
                min-height: 100vh;

                border: none;
            }

            .print-button {
                display: none;
            }

        }

    </style>

</head>


<body>

    <button
        type="button"
        class="print-button"
        onclick="window.print()"
    >
        Print
    </button>


    <main class="print-container">

        <section class="qr-card">

            <div class="brand">
                RoomLink
            </div>


            <div class="room-label">
                Classroom
            </div>


            <div class="room-name">
                {{ $room->room_name }}
            </div>


            <div class="qr-wrapper">

                {!! QrCode::size(420)->generate($qrData) !!}

            </div>


            <div class="location">

                <div class="building">

                    {{ $room->floor->building->building_name ?? 'Building' }}

                </div>


                <div class="floor">

                    Floor {{ $room->floor->floor_number ?? '—' }}

                </div>

            </div>


            <div class="instruction">

                Scan this QR code to identify this classroom.

            </div>


            @if($room->qr_code)

                <div class="qr-code-id">

                    {{ $room->qr_code }}

                </div>

            @endif

        </section>

    </main>

</body>

</html>