<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Download,
    FileText,
    Image as ImageIcon,
    MoreVertical,
    Pencil,
    Share2,
    Star,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import drive from '@/routes/drive';

const props = defineProps<{
    file: {
        id: number;
        name: string;
        extension: string | null;
        mime_type: string;
        size: number;
        starred: boolean;
    };
}>();

const emit = defineEmits<{
    rename: [file: { id: number; name: string }];
    preview: [file: { id: number; name: string; mime_type: string }];
    share: [file: { id: number; name: string; type: 'file' }];
}>();

const isImage = computed(() => props.file.mime_type.startsWith('image/'));
const isPreviewable = computed(
    () => isImage.value || props.file.mime_type === 'application/pdf',
);

const sizeLabel = computed(() => {
    const bytes = props.file.size;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
});

function toggleStar() {
    router.post(
        drive.star.toggle.url({ type: 'file', id: props.file.id }),
        {},
        { preserveScroll: true },
    );
}

function destroy() {
    if (!confirm(`¿Mover "${props.file.name}" a la papelera?`)) return;
    router.delete(drive.files.destroy.url({ file: props.file.id }), {
        preserveScroll: true,
    });
}

function download() {
    window.location.href = drive.files.download.url({ file: props.file.id });
}

function onClick() {
    if (isPreviewable.value) {
        emit('preview', {
            id: props.file.id,
            name: props.file.name,
            mime_type: props.file.mime_type,
        });
    } else {
        download();
    }
}
</script>

<template>
    <div
        class="group relative flex items-center gap-3 rounded-lg border bg-card p-3 hover:bg-accent"
    >
        <button
            class="flex flex-1 items-center gap-3 truncate text-left"
            @click="onClick"
        >
            <component
                :is="isImage ? ImageIcon : FileText"
                class="size-5 shrink-0"
                :class="isImage ? 'text-emerald-500' : 'text-muted-foreground'"
            />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ file.name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ sizeLabel }}</p>
            </div>
        </button>

        <Star
            v-if="file.starred"
            class="size-4 fill-yellow-400 text-yellow-400"
        />

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon-sm" class="opacity-0 group-hover:opacity-100">
                    <MoreVertical class="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="download">
                    <Download class="size-4" /> Descargar
                </DropdownMenuItem>
                <DropdownMenuItem @click="emit('rename', { id: file.id, name: file.name })">
                    <Pencil class="size-4" /> Renombrar
                </DropdownMenuItem>
                <DropdownMenuItem @click="emit('share', { id: file.id, name: file.name, type: 'file' })">
                    <Share2 class="size-4" /> Compartir
                </DropdownMenuItem>
                <DropdownMenuItem @click="toggleStar">
                    <Star class="size-4" />
                    {{ file.starred ? 'Quitar favorito' : 'Marcar favorito' }}
                </DropdownMenuItem>
                <DropdownMenuItem variant="destructive" @click="destroy">
                    <Trash2 class="size-4" /> Eliminar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
