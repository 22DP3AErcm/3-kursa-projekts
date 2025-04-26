<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectBlockController extends Controller
{
    /**
     * Check if user has write permissions for a project
     */
    private function checkWritePermissions(Project $project)
    {
        $user = Auth::user();
        
        // Project owner always has write permissions
        if ($project->user_id === $user->id) {
            return true;
        }
        
        // Check member role (viewers cannot edit)
        $member = $project->members()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }
        
        // Only members and co-owners can edit (viewers cannot)
        return in_array($member->pivot->role, ['member', 'co-owner']);
    }
    
    /**
     * Store a newly created block in storage.
     */
    public function store(Request $request, Project $project)
    {
        // Check write permissions first
        if (!$this->checkWritePermissions($project)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Get the highest order value
        $maxOrder = $project->blocks()->max('order') ?? -1;

        $block = new ProjectBlock([
            'title' => $request->title,
            'order' => $maxOrder + 1,
        ]);

        $project->blocks()->save($block);

        return response()->json($block, 201);
    }

    /**
     * Update the specified block in storage.
     */
    public function update(Request $request, ProjectBlock $block)
    {
        // Check write permissions first
        $project = $block->project;
        if (!$this->checkWritePermissions($project)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $block->update([
            'title' => $request->title,
        ]);

        return response()->json($block);
    }

    /**
     * Remove the specified block from storage.
     */
    public function destroy(ProjectBlock $block)
    {
        // Check write permissions first
        $project = $block->project;
        if (!$this->checkWritePermissions($project)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $block->delete();

        return response()->json(['message' => 'Block deleted successfully']);
    }

    /**
     * Reorder blocks.
     */
    public function reorder(Request $request, Project $project)
    {
        // Check write permissions first
        if (!$this->checkWritePermissions($project)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|integer|exists:project_blocks,id',
            'blocks.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->blocks as $blockData) {
            $block = ProjectBlock::find($blockData['id']);
            
            // Make sure the block belongs to the project
            if ($block->project_id !== $project->id) {
                continue;
            }
            
            $block->update(['order' => $blockData['order']]);
        }

        return response()->json(['message' => 'Blocks reordered successfully']);
    }
}