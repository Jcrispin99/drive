<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Folder as FolderIcon, MoreVertical, Pencil, Share2, Star, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import drive from '@/routes/drive';

const props = defineProps<{
    folder: {
        id: number;
        name: string;
        starred: boolean;
    };
}>();

const emit = defineEmits<{
    rename: [folder: { id: number; name: string }];
    share: [folder: { id: number; name: string; type: 'folder' }];
}>();

function toggleStar() {
    router.post(
        drive.star.toggle.url({ type: 'folder', id: props.folder.id }),
        {},
        { preserveScroll: true },
    );
}

function destroy() {
    if (!confirm(`¿Mover "${props.folder.name}" a la papelera?`)) return;
    router.delete(drive.folders.destroy.url({ folder: props.folder.id }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="group relative flex items-center gap-3 rounded-lg border bg-card p-3 hover:bg-accent">
        <Link
            :href="drive.folders.show.url({ folder: folder.id })"
            class="flex flex-1 items-center gap-3 truncate"
        >
            <FolderIcon class="size-5 shrink-0 text-blue-500" />
            <span class="truncate text-sm font-medium">{{ folder.name }}</span>
        </Link>

        <Star
            v-if="folder.starred"
            class="size-4 fill-yellow-400 text-yellow-400"
        />

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon-sm" class="opacity-0 group-hover:opacity-100">
                    <MoreVertical class="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="emit('rename', { id: folder.id, name: folder.name })">
                    <Pencil class="size-4" /> Renombrar
                </DropdownMenuItem>
                <DropdownMenuItem @click="emit('share', { id: folder.id, name: folder.name, type: 'folder' })">
                    <Share2 class="size-4" /> Compartir
                </DropdownMenuItem>
                <DropdownMenuItem @click="toggleStar">
                    <Star class="size-4" />
                    {{ folder.starred ? 'Quitar favorito' : 'Marcar favorito' }}
                </DropdownMenuItem>
                <DropdownMenuItem variant="destructive" @click="destroy">
                    <Trash2 class="size-4" /> Eliminar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
