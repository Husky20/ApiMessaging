<?php

namespace App\Service;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class MessageService
{
    const FILE_NAME = 'messages.json';
    private NormalizerInterface $normalizer;
    private SerializerInterface $serializer;
    private MessageRepository $messageRepository;
    private FileService $fileService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        NormalizerInterface $normalizer,
        SerializerInterface $serializer,
        MessageRepository $messageRepository,
        FileService $fileService,
        EntityManagerInterface $entityManager
    ) {
        $this->normalizer = $normalizer;
        $this->serializer = $serializer;
        $this->messageRepository = $messageRepository;
        $this->fileService = $fileService;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function saveMessages(array $data): string
    {
        if (!isset($data['messages']) || !is_array($data['messages'])) {
            throw new Exception('Invalid data format.');
        }

        $savedMessages = [];

        foreach ($data['messages'] as $messageData) {
            $message = $this->deserializeMessage(json_encode($messageData));
            $savedMessages[] = $message;
        }

        $this->saveMessagesToDatabase($savedMessages);

        $uuids = "";

        foreach ($savedMessages as $message) {
            $serializedMessage = $this->serializeMessage($savedMessages, 'json');
            $this->fileService->saveToFile($serializedMessage, self::FILE_NAME);
            $uuids .= "Message with uuid: " . $message->getUuid() . " saved. ";
        }

        return $uuids;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getMessages(?string $sortBy = null, ?string $sortOrder = 'asc'): array
    {
        $criteria = Criteria::create();


        if ($sortBy !== 'createdAt' && $sortBy !== 'uuid') {
            $criteria->orderBy(['uuid' => 'asc']);
        } else {
            $criteria->orderBy([$sortBy => $sortOrder]);
        }

        $messages = $this->messageRepository->matching($criteria);

        $result = [];

        foreach ($messages as $message) {
            $result[] = $this->normalizeMessage($message);
        }

        return $result;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalizeMessage(Message $message): array
    {
        return $this->normalizer->normalize($message, null, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ]);
    }

    public function findMessage(string $uuid): array
    {
        $message = $this->messageRepository->findOneBy(['uuid' => $uuid]);

        return $this->normalizeMessage($message);
    }

    private function serializeMessage(array $message, string $format): string
    {
        return $this->serializer->serialize($message, $format, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ]);
    }

    private function deserializeMessage(string $jsonData): Message
    {
        return $this->serializer->deserialize($jsonData, Message::class, 'json');
    }

    private function saveMessagesToDatabase(array $messages)
    {
        foreach ($messages as $message) {

            $message->setCreatedAt(new \DateTimeImmutable());
            $message->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($message);
        }
            $this->entityManager->flush();
    }
}