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
use OCP\AppFramework\Controller;

use OCA\PyPreview\Service\PreviewService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;

class PreviewAPIController extends Controller {
	/** @var PreviewService */
	private $service;

	public function __construct(
		string $appName,
		IRequest $request,
		PreviewService $service) {
		parent::__construct($appName, $request);

		$this->service = $service;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $fileId
	 * @param int $width
	 * @param int $height
	 */
	public function getPreview(
		string $fileId,
		int $width = PreviewService::PREVIEW_WIDTH_DEFAULT,
		int $height = PreviewService::PREVIEW_HEIGHT_DEFAULT
	) {
		$preview = $this->service->getPreview($fileId, $width, $height);
		return new DataDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'image/png']);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $fileId
	 * @param int $width
	 * @param int $height
	 *
	 */
	public function savePreview(string $fileId, int $width, int $height) {
		// TODO receive preview image from Python
	}
}
