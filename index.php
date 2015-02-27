<?php

require_once './vendor/autoload.php';
require_once './LoggerAppenderSMTPMailEvent.php';

/** Commandline access only */
if((php_sapi_name() !== 'cli')) {
    die('Ping Monitor can only be run using the php cli interpreter.');
}

/**
 * Load application config
 */
$yaml = new Symfony\Component\Yaml\Yaml();

/**
 * Load application logger
 */
$applicationFileLogAppender = new LoggerAppenderDailyFile('applicationErrorLoggerAppender');
$applicationFileLogAppender->setDatePattern('Y-m-d');
$applicationFileLogAppender->setFile('log/application-%s.log');
$applicationFileLogAppender->setAppend(true);
$applicationFileLogAppender->setLayout(new LoggerLayoutSimple());

$applicationErrorLogger = new Logger('errorLogger');
$applicationErrorLogger->setLevel(LoggerLevel::getLevelAll());
$applicationErrorLogger->addAppender($applicationFileLogAppender);

/**
 * Check config file and options are available
 */
try {
    
    $config = $yaml->parse('config.yml');

    if(!isset($config['mail']['host']) || empty($config['mail']['host'])) { throw new Exception('Config parameter missing "host", can be empty.'); }
    if(!isset($config['mail']['subject']) || empty($config['mail']['subject'])) { throw new Exception('Config parameter missing "subject", can be empty.'); }
    if(!isset($config['mail']['user'])) { throw new Exception('Config parameter missing "user", can be empty.'); }
    if(!isset($config['mail']['pass'])) { throw new Exception('Config parameter missing "pass", can be empty.'); }
    if(!isset($config['mail']['port']) || empty($config['mail']['port'])) { throw new Exception('Config parameter missing "port".'); }
    if(!isset($config['mail']['type']) || empty($config['mail']['type'])) { throw new Exception('Config parameter missing "type" valid values are: smtp plain login.'); }
    if(!isset($config['mail']['from']) || empty($config['mail']['from'])) { throw new Exception('Config parameter missing "from", must be a valid email address.'); }
    if(!isset($config['mail']['to']) || empty($config['mail']['to'])) { throw new Exception('Config parameter missing "to", must be a list of valid email addresses.'); }
    if(!isset($config['ips']) || empty($config['ips'])) { throw new Exception('Config parameter missing "ips", must be a list of valid ip addresses.'); }
    if(!isset($config['app']['frequency']) || ((int) $config['app']['frequency']) <= 1 ) { throw new Exception('Config parameter missing "app frequency", must be greater than 1.'); }
    
} catch (Exception $ex) {

    $message = "Configuration file incorrectly formatted or unavailable, expected config.yml." . PHP_EOL;
    $applicationErrorLogger->error($message . $ex->getMessage() . PHP_EOL);
    echo $message . $ex->getMessage() . PHP_EOL;
    exit(1);
    
}

/** 
 * load ip file and mail loggers 
 */
$ipFileLogAppender = new LoggerAppenderDailyFile('ipErrorLoggerAppender');
$ipFileLogAppender->setDatePattern('Y-m-d');
$ipFileLogAppender->setFile('log/ip-%s.log');
$ipFileLogAppender->setAppend(true);
$ipFileLogAppender->setLayout(new LoggerLayoutSimple());

$ipMailLogAppender = new LoggerAppenderSMTPMailEvent('ipLoggerAppender');
$ipMailLogAppender->setLayout(new LoggerLayoutSimple());
$ipMailLogAppender->setFrom($config['mail']['from']);
$ipMailLogAppender->setTo($config['mail']['to']);
$ipMailLogAppender->setPort($config['mail']['port']);
$ipMailLogAppender->setSmtpHost($config['mail']['host']);
$ipMailLogAppender->setSubject($config['mail']['subject']);
$ipMailLogAppender->setType($config['mail']['type']);

$ipMailLogger = new Logger('ipMailLogger');
$ipMailLogger->setLevel(LoggerLevel::getLevelAll());
$ipMailLogger->addAppender($ipMailLogAppender);

$ipFileLogger = new Logger('ipFileLogger');
$ipFileLogger->setLevel(LoggerLevel::getLevelAll());
$ipFileLogger->addAppender($ipFileLogAppender);

/**
 * Start application
 */
while(true) {
    
    /**
     * Ping addresses and log / report
     */
    foreach ($config['ips'] as $ip) {

        $ping = new JJG\Ping($ip);
        $latency = $ping->ping();

        if ($latency !== false) {
            
            $message = 'Pinging ' . $ip . ' - Latency is ' . $latency . 'ms' . PHP_EOL;
            
            /**
             * Log Successful pings to file only
             */
            $ipFileLogger->info($message);
            echo $message . PHP_EOL;

        } else {
            
            $message = 'Pinging ' . $ip . ' - Host could not be reached.' . PHP_EOL;
            
            /**
             * Log failed pings to file and email logs
             */
            $ipFileLogger->error($message);
            $ipMailLogger->error($message);
            echo $message . PHP_EOL;
        }
    }

    sleep((int) $config['app']['frequency']);
}

exit(0);