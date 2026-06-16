<!doctype html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Churchill Knight Umbrella</title>
    <style media="all" type="text/css">
        /* -------------------------------------
    GLOBAL RESETS
------------------------------------- */

        body {
            font-family: Helvetica, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 16px;
            line-height: 1.3;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        table {
            border-collapse: separate;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }

        table td {
            font-family: Helvetica, sans-serif;
            font-size: 16px;
            vertical-align: top;
        }

        /* -------------------------------------
    BODY & CONTAINER
------------------------------------- */

        body {
            background-color: #f4f5f6;
            margin: 0;
            padding: 0;
        }

        .body {
            background-color: #f4f5f6;
            width: 100%;
        }

        .container {
            margin: 0 auto !important;
            max-width: 600px;
            padding: 0;
            padding-top: 24px;
            width: 600px;
        }

        .content {
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
            max-width: 600px;
            padding: 0;
        }

        /* -------------------------------------
    HEADER, FOOTER, MAIN
------------------------------------- */

        .main {
            background: #ffffff;
            border: 1px solid #eaebed;
            border-radius: 16px;
            width: 100%;
        }

        .wrapper {
            box-sizing: border-box;
            padding: 24px;
        }

        .footer {
            clear: both;
            padding-top: 24px;
            text-align: center;
            width: 100%;
        }

        .footer td,
        .footer p,
        .footer span,
        .footer a {
            color: #9a9ea6;
            font-size: 12px;
            /* Reduced for subtext hierarchy */
            text-align: center;
            line-height: 1.5;
        }

        /* -------------------------------------
    TYPOGRAPHY
------------------------------------- */

        p {
            font-family: Helvetica, sans-serif;
            font-size: 16px;
            font-weight: normal;
            margin: 0;
            margin-bottom: 16px;
        }

        a {
            color: #0867ec;
            text-decoration: underline;
        }

        .compliance-links a {
            color: #9a9ea6 !important;
            text-decoration: underline;
        }

        /* -------------------------------------
    RESPONSIVE AND MOBILE FRIENDLY STYLES
------------------------------------- */
        @media only screen and (max-width: 640px) {

            .main p,
            .main td,
            .main span {
                font-size: 16px !important;
            }

            .wrapper {
                padding: 16px !important;
            }

            .content {
                padding: 0 !important;
            }

            .container {
                padding: 0 !important;
                padding-top: 8px !important;
                width: 100% !important;
            }

            .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
        <tr>
            <td>&nbsp;</td>
            <td class="container">
                <div class="content">

                    <div class="footer" style="padding-top: 0; padding-bottom: 16px;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="content-block">
                                    <img src="{{ config('app.url') }}/assets/email/umbrella-company-uk-logo.png" alt="Umbrella Company UK" width="140"
                                        style="outline: none; border: none; max-width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main">
                        <tr>
                            <td class="wrapper">
                                @if ($isHtml ?? true)
                                    {!! $bodyContent !!}
                                @else
                                    {!! nl2br(e($bodyContent)) !!}
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="footer">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="content-block">
                                    <span class="apple-link"><strong>Churchill Knight Umbrella</strong>, 1st Floor,
                                        Metropolitan House, Darkes Lane, Potters Bar, Herts, EN6 1AG</span>
                                    <br>
                                    <span style="font-size: 11px; color: #b1b5bc;">Registered in England & Wales No.
                                        04221122 | VAT No. GB 777 222 111</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="content-block compliance-links"
                                    style="padding-top: 12px; font-size: 11px; color: #9a9ea6;">
                                    This operational email was sent to
                                    <strong>{{ $toEmail ?? 'your registered address' }}</strong> because you have an
                                    active account or ongoing contract with Churchill Knight Umbrella (Lawful Basis:
                                    Performance of a Contract).
                                    <br><br>
                                    To review how we safeguard your data, read our <a
                                        href="{{ config('app.url') }}/privacy-policy" target="_blank">Privacy
                                        Policy</a>. To exercise your right to access, rectification, or erasure, contact
                                    our data team at <a
                                        href="mailto:privacy@churchill-knight.co.uk">privacy@churchill-knight.co.uk</a>.
                                    <br><br>
                                    <a href="{{ config('app.url') }}/email-preferences?email={{ urlencode($toEmail ?? '') }}"
                                        target="_blank">Manage Email Preferences</a> | <a
                                        href="{{ config('app.url') }}/unsubscribe?email={{ urlencode($toEmail ?? '') }}"
                                        target="_blank">Unsubscribe from all non-essential communications</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>
</body>

</html>
