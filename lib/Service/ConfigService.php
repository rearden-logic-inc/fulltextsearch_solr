<?php
declare(strict_types=1);


/**
 * FullTextSearch_Solr - Use Solr to index the content of your nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robert Robinson <rerobins@gmail.com>
 * @copyright 2019
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FullTextSearch_Solr\Service;


use OCA\FullTextSearch_Solr\AppInfo\Application;
use OCA\FullTextSearch_Solr\Exceptions\ConfigurationException;
use OCP\IConfig;
use OCP\PreConditionNotMetException;


/**
 * Class ConfigService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class ConfigService {


	const SOLR_SERVLET = 'solr_servlet';
	const SOLR_CORE = 'solr_core';


	public $defaults = [
		self::SOLR_SERVLET   => 'http://localhost:8983/solr/',
		self::SOLR_CORE      => 'nextcloud'
	];

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var MiscService */
	private $miscService;


	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig $config
	 * @param string $userId
	 * @param MiscService $miscService
	 */
	public function __construct(IConfig $config, $userId, MiscService $miscService) {
		$this->config = $config;
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	/**
	 * @return array
	 */
	public function getConfig(): array {
		$keys = array_keys($this->defaults);
		$data = [];

		foreach ($keys as $k) {
			$data[$k] = $this->getAppValue($k);
		}

		return $data;
	}


	/**
	 * @param array $save
	 */
	public function setConfig(array $save) {
		$keys = array_keys($this->defaults);

		foreach ($keys as $k) {
			if (array_key_exists($k, $save)) {
				$this->setAppValue($k, $save[$k]);
			}
		}
	}


	/**
	 * @return string
	 * @throws ConfigurationException
	 */
	public function getSolrServlet(): string {

		$strHost = $this->getAppValue(self::SOLR_SERVLET);
		if ($strHost === '') {
			throw new ConfigurationException(
				'Your Solr Platform is not configured properly'
			);
		}

		return trim($strHost);
	}


	/**
	 * @return string
	 * @throws ConfigurationException
	 */
	public function getSolrCore(): string {

		$index = $this->getAppValue(self::SOLR_CORE);
		if ($index === '') {
			throw new ConfigurationException(
				'Your Solr Platform is not configured properly'
			);
		}

		return $index;
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string {
		$defaultValue = null;
		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getAppValue(Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value) {
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue(string $key): string {
		return $this->config->deleteAppValue(Application::APP_NAME, $key);
	}

	/**
	 * Get a user value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getUserValue(string $key): string {
		$defaultValue = null;
		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getUserValue(
			$this->userId, Application::APP_NAME, $key, $defaultValue
		);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @throws PreConditionNotMetException
	 */
	public function setUserValue(string $key, string $value) {
		$this->config->setUserValue($this->userId, Application::APP_NAME, $key, $value);
	}

	/**
	 * Get a user value by key and user
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return string
	 */
	public function getValueForUser(string $userId, string $key): string {
		return $this->config->getUserValue($userId, Application::APP_NAME, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 *
	 * @throws PreConditionNotMetException
	 */
	public function setValueForUser($userId, $key, $value) {
		$this->config->setUserValue($userId, Application::APP_NAME, $key, $value);
	}

}
