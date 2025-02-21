<?php
/**
 * index.php
 *
 * This file is responsible for handling redirect by typed slug.
 *
 * @package    Link - molkuski
 * @author     misiektedi <michal@olkuski.com>
 * @version    1.0.0
 * @license    MIT
 * @link       https://l.molkuski.com
 * @created    2025-02-16
 */
declare(strict_types=1);

const MAIN_WEBSITE = 'https://molkuski.com';

$copyrightFooter = '&copy; ' . date('Y') . ' - misiektedi';

function redirect(string $location, int $responseCode = 301): void {
    http_response_code($responseCode);
    header('Location: ' . $location);
    exit();
}

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
 * Connection with an database
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
$requestUri = substr($_SERVER['REQUEST_URI'], 1);

/**
 * Checking, if user specified request uri.
 * If not - redirect to MAIN_WEBSITE.
 */
if (empty($requestUri)) {
    redirect(location: MAIN_WEBSITE, responseCode: 301);
}

/**
 * Fetching redirect location
 */
$model = new Model($pdo);
$redirectUrl = $model->getRedirectUrl($requestUri);

if ($redirectUrl) {
    $model->saveInHistory($redirectUrl);

    redirect(location: $redirectUrl, responseCode: 301);
} else {
    echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset='UTF-8'>
                <title>Link "/{$requestUri}" nie istnieje</title>
            </head>

            <body style='font-family: system-ui; background-color: #171717; color: #fff; padding: 20svh 10svw; display: flex; flex-direction: column; gap: 16px'>
                <main style='display: flex; flex-direction: column; gap: 16px'>
                    <div style='background-color:rgb(80, 27, 27); padding: 16px; border: 3px solid rgb(106, 27, 27); border-radius: 4px; display: flex; align-items: center; gap: 8px'>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-160q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v160q0 17 11.5 28.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                        
                        Link <b>"/{$requestUri}"</b> nie istnieje.
                    </div>

                    <div style='background-color:rgb(80, 70, 27); padding: 16px; border: 3px solid rgb(106, 101, 27); border-radius: 4px; display: flex; align-items: center; gap: 8px'>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M109-120q-11 0-20-5.5T75-140q-5-9-5.5-19.5T75-180l370-640q6-10 15.5-15t19.5-5q10 0 19.5 5t15.5 15l370 640q6 10 5.5 20.5T885-140q-5 9-14 14.5t-20 5.5H109Zm69-80h604L480-720 178-200Zm302-40q17 0 28.5-11.5T520-280q0-17-11.5-28.5T480-320q-17 0-28.5 11.5T440-280q0 17 11.5 28.5T480-240Zm0-120q17 0 28.5-11.5T520-400v-120q0-17-11.5-28.5T480-560q-17 0-28.5 11.5T440-520v120q0 17 11.5 28.5T480-360Zm0-100Z"/></svg>

                        Sprawdź, czy nie ma literówki w adresie i spróbuj ponownie.
                    </div>
                </main>

                <footer style='border-top: 1px solid rgb(52, 52, 52); color: rgb(146, 146, 146); padding-top: 16px;'>
                    — {$copyrightFooter}
                </footer>
            </body>
        </html>
    HTML;
}