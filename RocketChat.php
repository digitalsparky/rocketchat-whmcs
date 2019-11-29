<?php

namespace WHMCS\Module\Notification\RocketChat;
require_once dirname(__FILE__) . '/vendor/autoload.php';
use GuzzleHttp\Client as Guzzle;
use WHMCS\Exception;
use WHMCS\Module\Contracts\NotificationModuleInterface;
use WHMCS\Module\Notification\DescriptionTrait;
use WHMCS\Notification\Contracts\NotificationInterface;

class RocketChat implements NotificationModuleInterface {
	use DescriptionTrait;

	public function __construct() {
		$this->setDisplayName('RocketChat')
			->setLogoFileName('logo.svg');
	}

	public function settings() {
		return [
			'baseURL' => [
				'FriendlyName' => 'RocketChat Base URL',
				'Type' => 'text',
				'Description' => 'The base URL for your RocketChat instance (ie: https://chat.example.com)',
				'Placeholder' => "",
			],
		];
	}

	public function testConnection($settings) {
		return true;
	}

	public function notificationSettings() {
		return [
			'notificationToken' => [
				'FriendlyName' => 'Incoming WebHook Tokens',
				'Type' => 'text',
				'Description' => 'Choose the notification webhook tokens (comma delimit for more than one)',
				'Required' => true,
			],
		];
	}

	public function getDynamicField($fieldName, $settings) {
		return [];
	}

	public function sendNotification(NotificationInterface $notification, $moduleSettings, $notificationSettings) {
		$to = explode(',', $notificationSettings['notificationToken']);
		$to = array_filter(array_unique($to));
		if (!$to) {
			throw new Exception('No Notification tokens Found');
		}
		$notificationURL = $notification->getUrl();
		$postData = [
			'text' => sprintf("[%s](%s) \n %s", $notification->getTitle(), $notificationURL, $notification->getMessage()),
		];
		foreach ($notification->getAttributes() as $attribute) {
			$title_link = $attribute->getUrl();

			$attachment = [
				'title' => $attribute->getLabel(),
				'text' => $attribute->getValue(),
				'title_link' = ($title_link != "") ? $title_link : $notificationURL,
			}

			$postData['attachments'][] = $attachment;
		}
		foreach ($to as $k => $notificationToken) {
			$notificationURL = sprintf("%s/hooks/%s", $moduleSettings['baseURL'], $notificationToken);
			$client = new Guzzle();
			$response = $client->request('POST', $notificationURL, ['json' => $postData]);
			if (array_key_exists('error', $response)) {
				throw new Exception($response['error']);
			}
		}
	}
}
