<?php
/**
 * index.php
 *
 * A simple project to redirect users by short link specified in URL.
 *
 * @package    Link - molkuski
 * @author     misiektedi <michal@olkuski.com>
 * @version    1.1.0-2025.02
 * @license    MIT
 * @link       https://l.molkuski.com
 * @created    2025-02-16
 * @modified   2025-02-22
 */
declare(strict_types=1);

/**
 * Personalizable logo and footer
 * 
 * @var linkToLogo
 * @var copyrightFooter
 */
$linkToLogo = 'https://molkuski.com/assets/molkuski-logo-white.svg';
$copyrightFooter = '&copy; ' . date('Y') . ' - misiektedi';

/**
 * Redirect function
 * 
 * @param string location
 * @param integer responseCode
 */
function redirect(string $location, int $responseCode = 301): void {
    http_response_code($responseCode);
    header('Location: ' . $location);
    exit();
}

/**
 * Function which returns view content
 * 
 * @param string name
 * @param array values
 */
function view(string $name, array $values = []): string {
    $newKeys = array_map(fn($key) => "{(" . $key . ")}", array_keys($values));
    $newValues = array_combine($newKeys, array_values($values));

    $viewFileContent = file_get_contents(__DIR__ . '/views/' . $name . '.html');

    $viewContent = strtr($viewFileContent, $newValues);

    return $viewContent;
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
    $pdo = new PDO($config['database_driver'] . ':host=' . $config['database_host'] . ';dbname=' . $config['database_name'], $config['database_user'], $config['database_password']);
} catch (PDOException $e) {
    echo <<<HTML
        Wystąpił błąd. Spróbuj ponownie później.
    HTML;
}

/**
 * Saving request uri into variable
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Fetching redirect location
 */
$model = new Model($pdo);
$redirectUrl = $model->getRedirectUrl($requestUri);

if ($redirectUrl) {
    $model->saveInHistory($redirectUrl);

    redirect(location: $redirectUrl, responseCode: 301);
} else {
    echo view('link-not-exist', [
        'requestUri' => $requestUri,
        'copyrightFooter' => $copyrightFooter,
        'linkToLogo' => $linkToLogo,
    ]);
}