<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { User } from '@/types/models';
import { initializeStores } from '@/utils/init';

const form = useForm({
    name: '',
});

const createTeam = () => {
    form.post(route('teams.store'), {
        errorBag: 'createTeam',
        preserveScroll: true,
        onSuccess: () => {
            initializeStores();
        },
    });
};
const page = usePage<{
    auth: {
        user: User;
    };
}>();
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title> Organization Details</template>

        <template #description>
            Create a new organization to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <InputLabel value="Organization Owner" />

                <div class="flex items-center mt-2">
                    <img
                        class="object-cover w-12 h-12 rounded-full"
                        :src="page.props.auth.user.profile_photo_url"
                        :alt="page.props.auth.user.name" />

                    <div class="ms-4 leading-tight">
                        <div class="text-white">
                            {{ page.props.auth.user.name }}
                        </div>
                        <div class="text-sm text-muted">
                            {{ page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Organization Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    autofocus />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing">
                Create
            </PrimaryButton>
        </template>
    </FormSection>
</template>
