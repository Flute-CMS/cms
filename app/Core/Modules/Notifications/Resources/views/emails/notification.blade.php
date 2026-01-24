<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.5;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .content {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>{{ $title }}</h2>
        </div>

        <div class="content">
            <p>{!! nl2br(e($content)) !!}</p>

            @if (isset($components) && is_array($components))
                @foreach ($components as $component)
                    @if (isset($component['type']) && $component['type'] === 'actions' && isset($component['buttons']))
                        @foreach ($component['buttons'] as $button)
                            @if (isset($button['action']['url']))
                                <a href="{{ url($button['action']['url']) }}" class="button">{{ $button['label'] }}</a>
                            @endif
                        @endforeach
                    @elseif(isset($component['type']) && $component['type'] === 'code')
                        <div
                            style="background: #eee; padding: 10px; margin: 10px 0; font-family: monospace; text-align: center; font-size: 1.2em;">
                            {{ $component['code'] }}
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
