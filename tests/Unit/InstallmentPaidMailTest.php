<?php

namespace Tests\Unit;

use App\Mail\InstallmentPaidMail;
use Tests\TestCase;

class InstallmentPaidMailTest extends TestCase
{
    public function test_mail_contains_payment_details_and_escapes_user_content(): void
    {
        $mail = new InstallmentPaidMail('Maria', '123/2026', '<script>alert(1)</script>', 2, '01/09/2026', '1.000,50');
        $this->assertSame(InstallmentPaidMail::SUBJECT, $mail->envelope()->subject);
        $mail->assertSeeInHtml('Maria');
        $mail->assertSeeInHtml('123/2026');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $mail->render());
        $this->assertStringNotContainsString('<script>', $mail->render());
        $mail->assertSeeInHtml('01/09/2026');
        $mail->assertSeeInHtml('R$ 1.000,50');
        $this->assertSame(2, $mail->installmentNumber);
    }
}
