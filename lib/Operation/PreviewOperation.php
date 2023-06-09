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

namespace OCA\PyPreview\Operation;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\EventDispatcher\Event;
use OCA\WorkflowEngine\Entity\File;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;

use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IPreview;
use UnexpectedValueException;

use OCA\PyPreview\AppInfo\Application;
use OCA\PyPreview\BackgroundJob\Preview;
use OCA\PyPreview\Service\PreviewService;

class PreviewOperation implements ISpecificOperation {
	public const MODES = [
		'small',
		'medium',
		// 'large',
		'small;medium',
		// 'small;large',
		// 'medium;large',
		// 'small;medium;large',
	];

	/** @var LoggerInterface */
	private $logger;

	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IJobList */
	private $jobsList;

	/** @var IPreview */
	private $previewManager;

	/** @var PreviewService */
	private $previewService;

	public function __construct(
		IL10N $l,
		LoggerInterface $logger,
		IURLGenerator $urlGenerator,
		IJobList $jobsList,
		IPreview $previewManager,
		PreviewService $previewService
	) {
		$this->l = $l;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->jobsList = $jobsList;
		$this->previewManager = $previewManager;
		$this->previewService = $previewService;
	}

	public function validateOperation(string $name, array $checks, string $operation): void {
		if (!in_array($operation, PreviewOperation::MODES)) {
			throw new UnexpectedValueException($this->l->t('Choose a valid operation mode'));
		}
	}

	public function getDisplayName(): string {
		return $this->l->t('Preview generation');
	}

	public function getDescription(): string {
		return $this->l->t('Request preview generation to Python Preview Generator');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');
	}

	public function isAvailableForScope(int $scope): bool {
		return $scope === IManager::SCOPE_ADMIN;
	}

	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		$this->logger->error('[' . self::class . '] onEvent: ' . $eventName);
		if (!$event instanceof GenericEvent) {
			return;
		}
		try {
			if ($eventName === '\OCP\Files::postRename' || $eventName === '\OCP\Files::postCopy') {
				/** @var Node $oldNode */
				[$oldNode, $node] = $event->getSubject();
			} else {
				$node = $event->getSubject();
			}
			/** @var Node $node */

			// '', admin, 'files', 'path/to/file.txt'
			[,, $folder,] = explode('/', $node->getPath(), 4);
			if ($folder !== 'files' || $node instanceof Folder) {
				return;
			}

			if ($node->getMimePart() !== 'image'
				|| $node->getMimePart() == 'video' || $node->getMimePart() == 'audio') {
				return;
			}

			$matches = $ruleMatcher->getFlows(false);
			$operations = [];
			foreach ($matches as $match) {
				$matchOperations = explode(';', $match['operation']);
				foreach ($matchOperations as $operation) {
					if (!in_array($operation, $operations)) {
						array_push($operations, $operation);
					}
				}
			}

			// TODO: Uncomment preview check when it's tested and works
			// if ($this->previewManager->isAvailable($node)) {
			// 	return;
			// }
			if ($this->previewService->isPreviewAvailable($node->getId(), $node->getOwner()->getUID())) {
				return;
			}
			$this->jobsList->add(Preview::class, [
				'fileId' => $node->getId(),
				'ownerId' => $node->getOwner()->getUID(),
				'path' => $node->getPath(),
				'encrypted' => $node->isEncrypted(),
				'operations' => $operations,
			]);
		} catch (\OCP\Files\NotFoundException $e) {
		}
	}

	public function getEntityId(): string {
		return File::class;
	}
}
