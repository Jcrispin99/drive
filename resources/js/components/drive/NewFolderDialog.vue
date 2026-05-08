<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FolderPlus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import drive from '@/routes/drive';

const props = defineProps<{
    parentId: number | null;
}>();

const open = ref(false);
const form = useForm({ name: '', parent_id: props.parentId });

function submit() {
    form.parent_id = props.parentId;
    form.post(drive.folders.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset('name');
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">
                <FolderPlus class="size-4" /> Nueva carpeta
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nueva carpeta</DialogTitle>
                <DialogDescription>Ingresa el nombre de la carpeta.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="folder-name">Nombre</Label>
                    <Input id="folder-name" v-model="form.name" autofocus required />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Crear</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
