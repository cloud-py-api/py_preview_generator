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

use OC\Http\Client\ClientService;

class PreviewService {
	const PREVIEW_WIDTH_DEFAULT = 512;
	const PREVIEW_HEIGHT_DEFAULT = 512;

	/** @var ClientService */
	private $clientService;

	public function __construct(ClientService $clientService) {
		$this->clientService = $clientService;
	}

	public function getPreview(
		string $fileId,
		int $width = self::PREVIEW_WIDTH_DEFAULT,
		int $height = self::PREVIEW_HEIGHT_DEFAULT
	) {
		$preview = $this->generatePreview($fileId, $width, $height);
		return $preview;
	}

	private function generatePreview(string $fileId, int $width, int $height) {
		// TODO request to python script
		return null;
	}
}
