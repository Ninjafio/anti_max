<?php

namespace Flamix\Bitrix24\WooCommerce;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Helpers
{
    /**
     * Prepare CF7 Files to send to Bitrix24
     *
     * @param array $files
     * @return array
     * @throws \Exception
     */
    public static function prepareFiles(array $files)
    {
        $return = [];
        foreach ($files as $input_key => $input_value) {
            if (is_array($input_value))
                foreach ($input_value as $file_path) {
                    $name = explode('/', $file_path);
                    if (is_array($name))
                        $name = end($name);
                    else
                        throw new \Exception('Flamix-Bitrix24: Bad file name!');

                    $content = @file_get_contents($file_path);
                    if (empty($content))
                        throw new \Exception('Flamix-Bitrix24: Empty file content!');

                    $return[$input_key][] = [
                        'content' => base64_encode($content),
                        'file_name' => $name
                    ];

                    unset($content);
                }
        }

        return $return;
    }

    /**
     * Get saved email or admin email
     *
     * @return bool|mixed|void
     */
    public static function get_backup_email()
    {
        if (!empty(Settings::getOption('lead_backup_email')))
            return Settings::getOption('lead_backup_email');

        if (!empty(get_option('admin_email')))
            return get_option('admin_email');

        return false;
    }

    /**
     * Send error msg to BackUp email
     *
     * @param string $message
     * @return bool
     */
    public static function sendError($message = 'Something went wrong')
    {
        $to = self::get_backup_email();
        $subject = 'Flamix Bitrx24&WP plugin: Error';
        return wp_mail($to, $subject, $message);
    }

    /**
     * When saving email - check
     *
     * @param $option
     * @return bool|string
     */
    public static function isEmail($option)
    {
        if (filter_var($option, FILTER_VALIDATE_EMAIL))
            return $option;

        return false;
    }

    /**
     * When saving email - check
     *
     * @param $option
     * @return bool|string
     */
    public static function parseDomain($option)
    {
        $tmp = parse_url($option);
        if (!empty($tmp['host']))
            return $tmp['host'];

        return $option;
    }

    /**
     * My WP Log
     *
     * @param $message
     * @param array $context
     */
    public static function log($message, array $context = [])
    {
        $api_token = Settings::getOption('lead_api') ?? 'none';
        $date = date('Y-m-d');
        $log = new Logger(Settings::getPluginName());
        $log->pushHandler(new StreamHandler(plugin_dir_path(dirname(__FILE__)) . 'logs/' . $date . '-info-' . md5($api_token . $date) . '.log', Logger::DEBUG));
        $log->info($message, $context);
    }

    /**
     * Sending data to Bitrix24 plugin
     *
     * @param $data
     * @param string $actions
     * @return mixed
     */
    public static function send($data, $actions = 'lead/add')
    {
        return \Flamix\Bitrix24\Lead::getInstance()->changeSubDomain(Handlers::getSubDomain())->setDomain(Settings::getOption('lead_domain'))->setToken(Settings::getOption('lead_api'))->send($data, $actions);
    }
}