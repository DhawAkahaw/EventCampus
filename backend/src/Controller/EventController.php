<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $events = $entityManager
            ->getRepository(Event::class)
            ->findAll();

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'startAt' => $event->getStartAt()?->format('Y-m-d H:i:s'),
                'endAt' => $event->getEndAt()?->format('Y-m-d H:i:s'),
                'capacity' => $event->getCapacity(),
                'image' => $event->getImage(),
                'category' => $event->getCategory()?->getName(),
                'organizer' => $event->getOrganizer()?->getUserIdentifier(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/events', name: 'api_events_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'message' => 'Invalid JSON data.'
            ], 400);
        }

        $requiredFields = [
            'title',
            'description',
            'location',
            'startAt',
            'endAt',
            'capacity',
            'categoryId'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return $this->json([
                    'message' => "Missing field: $field"
                ], 400);
            }
        }

        $category = $entityManager
            ->getRepository(Category::class)
            ->find($data['categoryId']);

        if (!$category) {
            return $this->json([
                'message' => 'Category not found.'
            ], 404);
        }

        $event = new Event();

        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setLocation($data['location']);
        $event->setStartAt(new \DateTime($data['startAt']));
        $event->setEndAt(new \DateTime($data['endAt']));
        $event->setCapacity((int) $data['capacity']);
        $event->setCategory($category);
        $event->setCreatedAt(new \DateTimeImmutable());

        if (!empty($data['image'])) {
            $event->setImage($data['image']);
        }

        $user = $this->getUser();

        if ($user) {
            $event->setOrganizer($user);
        }

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json([
            'message' => 'Event created successfully.',
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'category' => $category->getName(),
                'organizer' => $user?->getUserIdentifier()
            ]
        ], 201);
    }

    #[Route('/api/events/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $event = $entityManager
            ->getRepository(Event::class)
            ->find($id);

        if (!$event) {
            return $this->json([
                'message' => 'Event not found.'
            ], 404);
        }

        return $this->json([
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'location' => $event->getLocation(),
            'startAt' => $event->getStartAt()?->format('Y-m-d H:i:s'),
            'endAt' => $event->getEndAt()?->format('Y-m-d H:i:s'),
            'capacity' => $event->getCapacity(),
            'image' => $event->getImage(),
            'category' => $event->getCategory()?->getName(),
            'organizer' => $event->getOrganizer()?->getUserIdentifier(),
        ]);
    }
}