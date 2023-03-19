<?php
/**
 * @copyright Copyright (c) 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @copyright Copyright (c) 2023 Alexander Piskun <bigcat88@icloud.com>
 *
 * @author 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\PyPreview\Service;

use OCP\IConfig;
use OCP\Files\IAppData;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

use OCA\PyPreview\AppInfo\Application;

class PreviewService {
	const PREVIEW_WIDTH_DEFAULT = 256;
	const PREVIEW_HEIGHT_DEFAULT = 256;

	const PYTHON_SERVICE_URL = 'http://localhost:9001';
	const PYTHON_THUMBNAIL_ROUTE = '/thumbnail';

	/** @var IClient */
	private $client;

	/** @var IConfig */
	private $config;

	/** @var IAppData */
	private $appData;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		IClientService $clientService,
		IConfig $config,
		IAppData $appData,
		LoggerInterface $logger
	) {
		$this->client = $clientService->newClient();
		$this->config = $config;
		$this->appData = $appData;
		$this->logger = $logger;
	}

	public function getPreview(
		string $fileId,
		string $ownerId,
		string $size = 'small'
	) {
		if ($this->isPreviewAvailable($fileId, $ownerId, $size)) {
			$previewFolder = $this->getAppDataFolder($ownerId);
			$previewFile = $previewFolder->getFile($fileId . '-' . $size . '.jpg');
			return $previewFile->getContent();
		}
		return null;
	}

	/**
	 * @param string $fileId
	 * @param string $ownerId
	 * @param string $size small, medium or large
	 */
	public function isPreviewAvailable(
		string $fileId,
		string $ownerId,
		string $size = 'small'
	) {
		$previewFolder = $this->getAppDataFolder($ownerId);
		try {
			$previewFile = $previewFolder->getFile($fileId . '-' . $size . '.jpg');
			return $previewFile->getSize() > 0;
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		}
		return false;
	}

	/**
	 * @param int $fileId
	 * @param string $ownerId
	 * @param string $path
	 * @param array $operations
	 * @return void
	 */
	public function proceedPreviewJob(
		int $fileId,
		string $ownerId,
		string $path,
		array $operations
	) {
		$this->logger->error('[' . self::class . '] proceedPreviewJob : ' . $fileId . ' ' . $ownerId . ' ' . $path . ' ' . $operations);
		$response = $this->client->get(
			PreviewService::PYTHON_SERVICE_URL . PreviewService::PYTHON_THUMBNAIL_ROUTE, [
			'query' => [
				'file_id' => $fileId,
				'user_id' => $ownerId,
			],
		]);
		return $response;
	}

	public function getAppDataFolder(string $ownerId) {
		try {
			$ncDataFolder = $this->config->getSystemValue('datadirectory', null);
			$ncInstanceId = $this->config->getSystemValue('instanceid', null);
			$appDataFolderPath = $ncDataFolder . '/appdata_' . $ncInstanceId . '/'
				. Application::APP_ID . '/' . $ownerId;
			if (!file_exists($appDataFolderPath)) {
				$this->appData->newFolder($ownerId);
			}
			return $this->appData->getFolder($ownerId);
		} catch(\OCP\Files\NotPermittedException $e) {
			$this->logger->error('[' . self::class . '] getAppDataFolder : ' . $e->getMessage());
			return null;
		} catch (\RuntimeException $e) {
			$this->logger->error('[' . self::class . '] getAppDataFolder : ' . $e->getMessage());
			return null;
		}
	}
}
