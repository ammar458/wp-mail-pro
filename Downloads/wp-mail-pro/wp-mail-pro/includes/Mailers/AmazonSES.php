<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class AmazonSES extends AbstractMailer {

    public function get_name(): string {
        return 'Amazon SES';
    }

    protected function get_option_key(): string {
        return 'wmp_amazonses';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'access_key' ) )
            && ! empty( $this->get( 'secret_key' ) )
            && ! empty( $this->get( 'region' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $region = $this->get( 'region', 'us-east-1' );

        $phpmailer->isSMTP();
        $phpmailer->Host       = "email-smtp.{$region}.amazonaws.com";
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = $this->get( 'access_key' );
        $phpmailer->Password   = $this->get( 'secret_key' );

        $this->set_from( $phpmailer );
    }

    public static function get_regions(): array {
        return [
            'us-east-1'      => 'US East (N. Virginia)',
            'us-east-2'      => 'US East (Ohio)',
            'us-west-2'      => 'US West (Oregon)',
            'ap-south-1'     => 'Asia Pacific (Mumbai)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'ca-central-1'   => 'Canada (Central)',
            'eu-central-1'   => 'Europe (Frankfurt)',
            'eu-west-1'      => 'Europe (Ireland)',
            'eu-west-2'      => 'Europe (London)',
            'sa-east-1'      => 'South America (Sao Paulo)',
        ];
    }
}
