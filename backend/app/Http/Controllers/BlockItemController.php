<?php

namespace App\Http\Controllers;

use App\Models\ProjectBlock;
use App\Models\BlockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockItemController extends Controller
{
    /**
     * Check if user has write permissions for a block's project
     */
    private function checkWritePermissions(ProjectBlock $block)
    {
        $user = Auth::user();
        $project = $block->project;
        
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
     * Store a newly created item in storage.
     */
    public function store(Request $request, ProjectBlock $block)
    {
        // Check write permissions first
        if (!$this->checkWritePermissions($block)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        // Get the highest order value for this block
        $maxOrder = $block->items()->max('order') ?? -1;

        $item = new BlockItem([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'order' => $maxOrder + 1,
        ]);

        $block->items()->save($item);

        return response()->json($item, 201);
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, BlockItem $item)
    {
        // Check write permissions first
        $block = $item->block;
        if (!$this->checkWritePermissions($block)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $item->update([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);

        return response()->json($item);
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(BlockItem $item)
    {
        // Check write permissions first
        $block = $item->block;
        if (!$this->checkWritePermissions($block)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }

    /**
     * Move an item to a different block.
     */
    public function moveItem(Request $request, BlockItem $item)
    {
        // Check write permissions first
        $currentBlock = $item->block;
        if (!$this->checkWritePermissions($currentBlock)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        $request->validate([
            'target_block_id' => 'required|integer|exists:project_blocks,id',
            'position' => 'nullable|integer|min:0',
        ]);

        $targetBlock = ProjectBlock::find($request->target_block_id);
        
        // Ensure the target block is in the same project
        if ($targetBlock->project_id !== $currentBlock->project_id) {
            return response()->json(['message' => 'Cannot move items between different projects'], 400);
        }

        // Get the highest order in the target block if position not specified
        $position = $request->position ?? $targetBlock->items()->max('order') + 1;
        
        // Update the item
        $item->update([
            'project_block_id' => $targetBlock->id,
            'order' => $position,
        ]);

        return response()->json(['message' => 'Item moved successfully']);
    }

    /**
     * Reorder items within a block.
     */
    public function reorder(Request $request, ProjectBlock $block)
    {
        // Check write permissions first
        if (!$this->checkWritePermissions($block)) {
            return response()->json(['message' => 'Viewers cannot modify project content'], 403);
        }
        
        // Continue with existing logic...
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:block_items,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $itemData) {
            $item = BlockItem::find($itemData['id']);
            
            // Make sure the item belongs to the block
            if ($item->project_block_id !== $block->id) {
                continue;
            }
            
            $item->update(['order' => $itemData['order']]);
        }

        return response()->json(['message' => 'Items reordered successfully']);
    }
}