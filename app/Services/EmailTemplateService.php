<?php

namespace App\Services;

class EmailTemplateService
{
    private function baseTemplate(string $name, string $title, string $content): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="ar">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: 'Arial', sans-serif;
                    background-color: #f7f8f9;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 650px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }
                .content {
                    padding: 40px;
                    color: #333333;
                }
                .content .first-logo {
                    height: 50px;
                    width: auto;
                }
                .content .second-logo {
                    height: 15px;
                    width: auto;
                }
                .content h1 {
                    font-size: 30px;
                    font-weight: bold;
                    margin: 35px 0;
                    color: #333;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .content p {
                    margin: 5px 0;
                    font-size: 16px;
                }
                .content .greeting {
                    margin-top: 30px;
                    margin-bottom: 20px;
                    font-size: 16px;
                }
                .content .cheers {
                    margin-top: 30px;
                    font-size: 16px;
                }
                .footer-container {
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #777777;
                    border-radius: 0 0 10px 10px;
                }
                .footer-container p {
                    margin: 1px 0;
                }
                .footer-container p:first-child {
                    font-weight: bold;
                    font-size: 12px;
                }
                .footer-container a {
                    color: #3ea4e4;
                    text-decoration: none;
                }
                .footer-container span {
                    margin: 0 8px;
                }
                table.full-width {
                    width: 100%;
                    background-color: #f7f8f9;
                }
                td.container-padding {
                    padding: 50px;
                }
            </style>
        </head>
        <body>
            <table class="full-width">
                <tr>
                    <td class="container-padding">
                        <div class="email-container">
                            <div class="content">
                                <img class="first-logo" src="http://backend.paltelmonitor.com/content/company-logo.png" alt="Company Logo">
                                <h1>{$title}</h1>
                                <p class="greeting">Hello {$name}</p>
                                {$content}
                            </div>
                        </div>
                        <div class="footer-container">
                            <p><small>Copyright © " . date('Y') . " Hosam M. Alashi. All Rights Reserved.</small></p>
                            <p><small>+972 597 408-508 <span>|</span> tsamara@nstechs.co</small></p>
                        </div>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    public function forgotPasswordTemplate(string $name, string $resetLink): string
    {
        $content = <<<HTML
            We received a request to reset your password. Please click the link below to set a new password:
            <p><a href="{$resetLink}" style="color: #3ea4e4; text-decoration: none; font-weight: bold;">Reset Password Link</a></p>
            <br>
            If you did not request this, please ignore this email.
        HTML;

        return $this->baseTemplate($name, 'Password Reset Request', $content);
    }

    public function newUserTemplate(string $name, string $email, string $password): string
    {
        $content = <<<HTML
            Welcome to our company! Your account has been created successfully. Below are your account details:
            <br><br>
            Email: <strong>{$email}</strong><br>
            Password: <strong>{$password}</strong>
        HTML;

        return $this->baseTemplate($name, 'Account Creation Confirmation', $content);
    }

    public function passwordChangedTemplate(string $name, string $email, string $password): string
    {
        $content = <<<HTML
            Your password has been successfully changed. Below are your new login details:
            <br><br>
            Email: <strong>{$email}</strong><br>
            New Password: <strong>{$password}</strong><br><br>
            Please make sure to keep your password secure.
        HTML;

        return $this->baseTemplate($name, 'Password Changed', $content);
    }

    public function basicTemplate(string $name, string $title, string $content): string
    {
        return $this->baseTemplate($name, $title, $content);
    }
}
