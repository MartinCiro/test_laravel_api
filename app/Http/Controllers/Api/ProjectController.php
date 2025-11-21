<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\Projects\Ports\ProjectServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projectService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = $this->projectService->getUserProjects($request->user()->id);

            return response()->json([
                'message' => 'Projects retrieved successfully',
                'data' => array_map(function ($project) {
                    return [
                        'id' => $project->getId()->getValue(),
                        'name' => $project->getName(),
                        'description' => $project->getDescription(),
                        'status' => $project->getStatus()->value,
                        'user_id' => $project->getUserId()->getValue(),
                        'created_at' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                        'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $projects)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $project = $this->projectService->createProject($validated, $request->user()->id);

            return response()->json([
                'message' => 'Project created successfully',
                'data' => [
                    'id' => $project->getId()->getValue(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'status' => $project->getStatus()->value,
                    'user_id' => $project->getUserId()->getValue(),
                    'created_at' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['name' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $project = $this->projectService->getProjectById($id, $request->user()->id);

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Project retrieved successfully',
                'data' => [
                    'id' => $project->getId()->getValue(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'status' => $project->getStatus()->value,
                    'user_id' => $project->getUserId()->getValue(),
                    'created_at' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $project = $this->projectService->updateProject($id, $validated, $request->user()->id);

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Project updated successfully',
                'data' => [
                    'id' => $project->getId()->getValue(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'status' => $project->getStatus()->value,
                    'user_id' => $project->getUserId()->getValue(),
                    'created_at' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['name' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $deleted = $this->projectService->deleteProject($id, $request->user()->id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Project deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
            ]);

            $project = $this->projectService->updateProjectStatus($id, $validated['status'], $request->user()->id);

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Project status updated successfully',
                'data' => [
                    'id' => $project->getId()->getValue(),
                    'name' => $project->getName(),
                    'status' => $project->getStatus()->value,
                    'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['status' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update project status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}