<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, File as FileIcon, Folder as FolderIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    type: 'file' | 'folder' | null;
    item: { id: number; name: string; path?: string; mime_type?: string; size?: number } | null;
    token: string;
    children?: Array<{ id: number; name: string }>;
    files?: Array<{ id: number; name: string; mime_type: string; size: number }>;
}>();

defineOptions({ layout: null });

const downloadUrl = `/s/${props.token}/download`;
</script>

<template>
    <Head :title="item?.name ?? 'Compartido'" />

    <div class="min-h-screen bg-background">
        <div class="mx-auto max-w-3xl p-6">
            <div v-if="!item" class="rounded-xl border bg-card p-8 text-center">
                Enlace no válido.
            </div>

            <div v-else-if="type === 'file'" class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <FileIcon class="size-6 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-lg font-medium">{{ item.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ item.mime_type }}</p>
                    </div>
                    <Button as-child>
                        <a :href="downloadUrl"><Download class="size-4" /> Descargar</a>
                    </Button>
                </div>
            </div>

            <div v-else class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <FolderIcon class="size-6 text-blue-500" />
                    <p class="text-lg font-medium">{{ item.name }}</p>
                </div>

                <ul class="space-y-2">
                    <li
                        v-for="child in children ?? []"
                        :key="`f-${child.id}`"
                        class="flex items-center gap-3 rounded-lg border bg-card p-3"
                    >
                        <FolderIcon class="size-5 text-blue-500" />
                        <span>{{ child.name }}</span>
                    </li>
                    <li
                        v-for="file in files ?? []"
                        :key="`d-${file.id}`"
                        class="flex items-center gap-3 rounded-lg border bg-card p-3"
                    >
                        <FileIcon class="size-5 text-muted-foreground" />
                        <span class="flex-1">{{ file.name }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
