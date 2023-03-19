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

namespace OCA\PyPreview\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Class Preview
 *
 * Background job for generating previews using external Python module
 * 
 * @package OCA\PyPreview\BackgroundJob
 */
class Preview extends QueuedJob {
	/** @var IConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ITimeFactory $time,
		IConfig $config,
		LoggerInterface $logger
	) {
		// TODO
		parent::__construct($time);
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param mixed $argument
	 */
	protected function run($argument) {
		// TODO
		$this->logger->error('[' . self::class . '] PreviewBackgroundJob executed. Argument: ' . json_encode($argument) . '.');
	}
}
