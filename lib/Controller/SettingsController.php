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


namespace OCA\FullTextSearch_Solr\Controller;


use Exception;
use OCA\FullTextSearch_Solr\AppInfo\Application;
use OCA\FullTextSearch_Solr\Service\ConfigService;
use OCA\FullTextSearch_Solr\Service\MiscService;
use OCA\FullTextSearch_Solr\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class SettingsController
 *
 * @package OCA\FullTextSearch_ElasticSearch\Controller
 */
class SettingsController extends Controller {


	/** @var ConfigService */
	private $configService;

	/** @var SettingsService */
	private $settingsService;

	/** @var MiscService */
	private $miscService;


	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest $request
	 * @param ConfigService $configService
	 * @param SettingsService $settingsService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, ConfigService $configService, SettingsService $settingsService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->configService = $configService;
		$this->settingsService = $settingsService;
		$this->miscService = $miscService;
	}


	/**
	 * @return DataResponse
	 * @throws Exception
	 */
	public function getSettingsAdmin(): DataResponse {
		$data = $this->configService->getConfig();

		return new DataResponse($data, Http::STATUS_OK);
	}


	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 * @throws Exception
	 */
	public function setSettingsAdmin(array $data): DataResponse {

		if ($this->settingsService->checkConfig($data)) {
			$this->configService->setConfig($data);
		}

		return $this->getSettingsAdmin();
	}


}

