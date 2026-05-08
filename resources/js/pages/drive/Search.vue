<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import FileBrowser from '@/components/drive/FileBrowser.vue';
import { Input } from '@/components/ui/input';
import drive from '@/routes/drive';

const props = defineProps<{
    q: string;
    folders: Array<{ id: number; name: string; starred: boolean }>;
    files: Array<{
        id: number;
        name: string;
        extension: string | null;
        mime_type: string;
        size: number;
        starred: boolean;
    }>;
}>();

const query = ref(props.q);
let timer: ReturnType<typeof setTimeout> | null = null;

watch(query, (val) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(drive.search.index.url(), { q: val }, { preserveState: true, replace: true });
    }, 250);
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Búsqueda', href: drive.search.index().url }],
    },
});
</script>

<template>
    <Head title="Búsqueda" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Buscar</h1>
        <Input v-model="query" placeholder="Nombre de archivo o carpeta…" autofocus />

        <FileBrowser v-if="q !== ''" :folders="folders" :files="files" />
        <p v-else class="text-sm text-muted-foreground">Escribe para buscar.</p>
    </div>
</template>
