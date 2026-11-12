@inject('emailTheme', 'Pcteckserv\CmsCore\Mail\EmailTheme')
<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $email['subject'] }}</title>
</head>
<body class="cms-email" style="margin:0;padding:0;background-color:#f4f4f4;color:#111111;font-family:Arial,Helvetica,sans-serif;line-height:1.6">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4">
        <tr><td align="center" style="padding:32px 12px">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background-color:#ffffff">
                <tr><td style="padding:24px 32px;background-color:#000000;color:#ffffff">
                    @include('cms-core::emails.header')
                </td></tr>
                <tr><td class="cms-email__content" style="padding:32px">
                    @yield('email-content')
                </td></tr>
                <tr><td style="padding:24px 32px;border-top:1px solid #eeeeee;font-size:12px">
                    @include('cms-core::emails.footer')
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
