<?php



use Xakki\Emailer\ConfigService;
use Xakki\Emailer\Emailer;
use Xakki\Emailer\Exception;
use Xakki\Emailer\Model;
use Xakki\Emailer\Transports;

define('NOTIFY_NEWS', 'Новости');
define('CAMPANY_NEWS1', 'Тестирование сервиса');

$projectId = 1;

$config = new ConfigService();
$config->db['pass'] = 'CHENGE_ME';

$logger = new \Xakki\Emailer\test\phpunit\Logger();
$emailer = new Emailer($config, $logger);

try {
    $emailer->getProject($projectId);
    $campany = Model\Campany::findOne(['project_id' => $projectId]);
} catch (exception\DataNotFound $e) {
    exit('+++');
    $emailer->getDb()->beginTransaction();
    // Add project
    $project = $emailer->createProject('Test project', [
        Model\Template::NAME_HOST => 'example.com',
        Model\Template::NAME_ROUTE => '/my-emailer',
//        Model\Template::NAME_REPLY => ['Robot' => 'robot@example.com'],
        Model\Template::NAME_TIMEZONE => 'UTC',
        Model\Template::NAME_LANG => 'ru',
        Model\Template::NAME_URL_LOGO => 'https://example.com/logo.png',
    ]);

    // Add tpl wraper
    $tplWraper = $project->createTplWraper('Base', file_get_contents(__DIR__ . '/tpl/wraper1.php'));

    // Add tpl content
    $tplContent = $project->createTplContent('News1', file_get_contents(__DIR__ . '/tpl/content1.php'));

    // Add tpl head
    $tplContent = $project->createTplBlock('head1', file_get_contents(__DIR__ . '/tpl/head1.php'));

    // Add tpl footer
    $tplContent = $project->createTplBlock('footer1', file_get_contents(__DIR__ . '/tpl/footer1.php'));

    $notifyNews = $project->createNotify(NOTIFY_NEWS);

    // Add transport
    $smtp = new Transports\Smtp();
    // Add notify
    $smtp->fromEmail = 'robot@example.com';
    $smtp->fromName = 'Robot';
    $smtp->dkim = __DIR__ . '/tpl/dkim.key';
    $transport = $project->createTransport($smtp);

    // Add campany
    $campany = $project->createCampany(CAMPANY_NEWS1, $tplWraper, $tplContent, $notifyNews);

    $emailer->getDb()->commit();
}

$mail = $emailer->getNewMail();
$mail->setEmail('test@xakki.com');
$mail->setEmailName('Test User');
$mail->setData([
    'link' => 'http://xakki.ru',
]);
$hash = $emailer
    ->getNewSender($campany->project_id, $campany->id)
    ->send($mail);

echo $hash;
//$emailer->mail(CAMPANY_NEWS1, 'user1@xakki.ru', 'Юзер 1', [
//    'link' => 'http://xakki.ru',
//]);
