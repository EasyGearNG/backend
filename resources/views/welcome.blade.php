<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>EASYGEAR</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,600,700" rel="stylesheet" />

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            }
            
            .container {
                text-align: center;
            }
            
            .logo {
                font-size: 5rem;
                font-weight: 700;
                color: #fff;
                text-transform: uppercase;
                letter-spacing: 0.3em;
                text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
                animation: glow 2s ease-in-out infinite alternate;
            }
            
            .tagline {
                margin-top: 1rem;
                font-size: 1.2rem;
                color: rgba(255, 255, 255, 0.7);
                letter-spacing: 0.2em;
            }
            
            @keyframes glow {
                from {
                    text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
                }
                to {
                    text-shadow: 0 0 40px rgba(255, 255, 255, 0.6), 0 0 60px rgba(100, 149, 237, 0.4);
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="logo">EASYGEAR</h1>
            <p class="tagline">Your Premium Gear Destination</p>
        </div>
    </body>
</html>
