<?php
/**
 * index.php
 *
 * A simple project to redirect users by short link specified in URL.
 *
 * @package    Links - molkuski
 * @author     misiektedi <michal@olkuski.com>
 * @version    1.2.0-2025.04
 * @license    MIT
 * @link       https://l.molkuski.com
 * @created    2025-02-16
 * @modified   2025-04-24
 */
declare( strict_types=1 );

/**
 * Program defines
 */
define( 'ML_NAME',      'Links - molkuski' );
define( 'ML_VERSION',   '1.2.0-2025.04' );
define( 'ML_AUTHOR',    'misiektedi <michal@olkuski.com>' );

/**
 * Redirect function
 * 
 * @param string location
 * @param integer responseCode
 */
function redirect( string $location, int $responseCode = 301 ): void {
    http_response_code( $responseCode );
    header( 'Location: ' . $location );
    exit();
}

/**
 * Message displaying function
 * 
 * @param string content
 */
function displayMessage( string $content ): void {
    header( 'Content-Type: text/plain' );

    $message        = PHP_EOL . str_repeat( ' ', 4 ) . $content . PHP_EOL;

    $message        .= PHP_EOL . '╭' . str_repeat( '╶', 48 ) . '╮';
    $message        .= PHP_EOL . '╵ ' . ML_VERSION;
    $message        .= PHP_EOL . '╵ ' . ML_AUTHOR;
    $message        .= PHP_EOL . '╰' . str_repeat( '╶', 48 ) . '╯';

    echo $message;
    exit();
}

/**
 * Object-oriented representation of tables in database
 */
class Model {
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRedirectUrl(string $requestUri): string|bool
    {
        $stmt = $this->pdo->prepare("SELECT redirect_url FROM links WHERE request_uri = :uri");
        $stmt->execute(['uri' => $requestUri]);
        $redirectUrl = $stmt->fetchColumn();

        return $redirectUrl;
    }

    public function saveInHistory(string $redirectUrl): void
    {
        $addressIp = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $stmt = $this->pdo->prepare("INSERT INTO links_history (redirect_url, ip_address, user_agent, redirected_at) VALUES (:redirectUrl, :ipAddress, :userAgent, NOW())");
        $stmt->execute([
            'redirectUrl' => $redirectUrl,
            'ipAddress' => $addressIp,
            'userAgent' => $userAgent,
        ]);

        return;
    }
}

/**
 * Connection with a database
 */
$config = require_once __DIR__ . '/Config.php';

try {
    $pdo = new PDO( $config['database_driver'] . ':host=' . $config['database_host'] . ';dbname=' . $config['database_name'], $config['database_user'], $config['database_password'] );
} catch (PDOException $e) {
    displayMessage( content: 'Wystąpił błąd. Spróbuj ponownie później.' );
}

/**
 * Saving request uri into variable
 */
$requestUri         = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ?? '/';

/**
 * About app endpoint
 */
if ( $requestUri === '/about' ) {
    displayMessage( content: ML_NAME . str_repeat( ' ', 8 ) . ML_VERSION  . str_repeat( ' ', 8 ) . ML_AUTHOR);
}

/**
 * Fetching redirect location
 */
$model              = new Model( $pdo );
$redirectUrl        = $model->getRedirectUrl( $requestUri );

if ( $redirectUrl ) {
    $model->saveInHistory( $redirectUrl );

    redirect( location: $redirectUrl, responseCode: 301 );
} else {
    displayMessage( content: 'Podany link nie istnieje.' );
}