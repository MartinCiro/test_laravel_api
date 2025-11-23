<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\Tasks\Ports\TaskServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private TaskServiceInterface $taskService
    ) {}

    public function index(Request $request, int $projectId): JsonResponse
    {
        try {
            // Verificar que el proyecto pertenece al usuario (podrías agregar esta validación)
            $tasks = $this->taskService->getProjectTasks($projectId, $request->user()->id);

            return response()->json([
                'message' => 'Tasks retrieved successfully',
                'data' => array_map(function ($task) {
                    return [
                        'id' => $task->getId()->getValue(),
                        'title' => $task->getTitle(),
                        'description' => $task->getDescription(),
                        'status' => $task->getStatus()->value,
                        'due_date' => $task->getDueDate()?->format('Y-m-d'),
                        'project_id' => $task->getProjectId()->getValue(),
                        'user_id' => $task->getUserId()->getValue(),
                        'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                        'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $tasks)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date',
            ]);

            $task = $this->taskService->createTask($validated, $projectId, $request->user()->id);

            return response()->json([
                'message' => 'Task created successfully',
                'data' => [
                    'id' => $task->getId()->getValue(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus()->value,
                    'due_date' => $task->getDueDate()?->format('Y-m-d'),
                    'project_id' => $task->getProjectId()->getValue(),
                    'user_id' => $task->getUserId()->getValue(),
                    'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['title' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id, $request->user()->id);

            if (!$task || $task->getProjectId()->getValue() !== $projectId) {
                return response()->json([
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Task retrieved successfully',
                'data' => [
                    'id' => $task->getId()->getValue(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus()->value,
                    'due_date' => $task->getDueDate()?->format('Y-m-d'),
                    'project_id' => $task->getProjectId()->getValue(),
                    'user_id' => $task->getUserId()->getValue(),
                    'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date',
            ]);

            $task = $this->taskService->updateTask($id, $validated, $request->user()->id);

            if (!$task || $task->getProjectId()->getValue() !== $projectId) {
                return response()->json([
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Task updated successfully',
                'data' => [
                    'id' => $task->getId()->getValue(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus()->value,
                    'due_date' => $task->getDueDate()?->format('Y-m-d'),
                    'project_id' => $task->getProjectId()->getValue(),
                    'user_id' => $task->getUserId()->getValue(),
                    'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['title' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id, $request->user()->id);

            if (!$task || $task->getProjectId()->getValue() !== $projectId) {
                return response()->json([
                    'message' => 'Task not found'
                ], 404);
            }

            $deleted = $this->taskService->deleteTask($id, $request->user()->id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Failed to delete task'
                ], 500);
            }

            return response()->json([
                'message' => 'Task deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:todo,in_progress,done',
            ]);

            $task = $this->taskService->updateTaskStatus($id, $validated['status'], $request->user()->id);

            if (!$task || $task->getProjectId()->getValue() !== $projectId) {
                return response()->json([
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Task status updated successfully',
                'data' => [
                    'id' => $task->getId()->getValue(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()->value,
                    'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['status' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}