<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResolveResourceFileContentUrlSubscriber implements EventSubscriberInterface
{
    private $generator;
    private $nodeRepository;

    public function __construct(UrlGeneratorInterface $generator, ResourceNodeRepository $nodeRepository)
    {
        $this->generator = $generator;
        $this->nodeRepository = $nodeRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPreSerialize', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onPreSerialize(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) ||
            //!\is_a($attributes['resource_class'], ResourceFile::class, true)
            !\is_a($attributes['resource_class'], AbstractResource::class, true)
        ) {
            return;
        }
        $mediaObjects = $controllerResult;

        if (!is_iterable($mediaObjects)) {
            $mediaObjects = [$mediaObjects];
        }
        //error_log($request->get('getFile'));
        //$getFile = $request->get('getFile');
        $getFile = true;
        foreach ($mediaObjects as $mediaObject) {
            if (!$mediaObject instanceof AbstractResource) {
                continue;
            }
            if ($mediaObject->hasResourceNode()) {
                $params = [
                    'id' => $mediaObject->getResourceNode()->getId(),
                    'tool' => $mediaObject->getResourceNode()->getResourceType()->getTool()->getName(),
                    'type' => $mediaObject->getResourceNode()->getResourceType()->getName(),
                ];

                $mediaObject->contentUrl = $this->generator->generate('chamilo_core_resource_view_file', $params);

                if ($getFile && $mediaObject->getResourceNode()->hasResourceFile()) {
                    //$mediaObject->contentFile = $this->nodeRepository->getResourceNodeFileContent($mediaObject->getResourceNode());
                }
            }
        }
    }
}
