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

namespace OCA\PyPreview\Controller;

use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;

use OCA\PyPreview\Service\PreviewService;
use OCP\AppFramework\Http\Response;
use OCP\Files\IAppData;
use Psr\Log\LoggerInterface;

class PreviewAPIController extends Controller {
	/** @var PreviewService */
	private $service;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IAppData */
	private $appData;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		string $appName,
		IRequest $request,
		IRootFolder $iRootFolder,
		IAppData $appData,
		PreviewService $service,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request);

		$this->rootFolder = $iRootFolder;
		$this->appData = $appData;
		$this->service = $service;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $fileId
	 * @param string $width
	 * @param int $height
	 */
	public function getPreview(
		int $fileId,
		string $ownerId,
		string $size = 'small'
	) {
		$preview = $this->service->getPreview($fileId, $ownerId, $size);
		return new DataDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'image/png']);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param int $file_id
	 * @param string $user_id
	 */
	public function getFileContents(int $file_id, string $user_id) {
		$userFolder = $this->rootFolder->getUserFolder($user_id);
		$nodes = $userFolder->getById($file_id);
		if (count($nodes) === 0) {
			return new DataDisplayResponse([], Http::STATUS_NOT_FOUND);
		}
		/** @var \OCP\Files\File */
		$file = $nodes[0];
		$contents = $file->getContent();
		$response = new DataDisplayResponse($contents, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
		$response->cacheFor(3600 * 24, false, true);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * Receive preview image from Python request
	 */
	public function savePreview(int $file_id, string $user_id, string $size = 'small') {
		$uploadedFile = $this->request->getUploadedFile('data');
		if (!$uploadedFile['error'] && $uploadedFile['size'] === 0) {
			return new DataDisplayResponse([], Http::STATUS_BAD_REQUEST);
		}
		$this->logger->error('[' . self::class . '] savePreview: ' . $uploadedFile['error'] . ' ' . $uploadedFile['size']);
		$imageData = file_get_contents($uploadedFile['tmp_name']);
		$userFolder = $this->rootFolder->getUserFolder($user_id);
		$nodes = $userFolder->getById($file_id);
		if (count($nodes) === 0) {
			return new DataDisplayResponse([], Http::STATUS_NOT_FOUND);
		}
		/** @var \OCP\Files\File */
		$file = $nodes[0];
		$imageName = $file->getId() . '-' . $size . '.jpg';
		$previewFolder = $this->appData->getFolder($user_id);
		$previewFolder->newFile($imageName)->putContent($imageData);
		$response = new Response();
		$response->setStatus(Http::STATUS_OK);
		return $response;
	}
}
