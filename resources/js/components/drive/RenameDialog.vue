<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import drive from '@/routes/drive';

const props = defineProps<{
    item: { kind: 'folder' | 'file'; id: number; name: string };
}>();

const emit = defineEmits<{ close: [] }>();

const open = ref(true);
const form = useForm({ name: props.item.name });

watch(open, (val) => {
    if (!val) emit('close');
});

function submit() {
    const url =
        props.item.kind === 'folder'
            ? drive.folders.update.url({ folder: props.item.id })
            : drive.files.update.url({ file: props.item.id });

    form.patch(url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Renombrar {{ item.kind === 'folder' ? 'carpeta' : 'archivo' }}</DialogTitle>
                <DialogDescription>Ingresa el nuevo nombre.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="rename-input">Nombre</Label>
                    <Input id="rename-input" v-model="form.name" autofocus />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Guardar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
