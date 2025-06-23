<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

interface Category {
    id: number
    title: string
}

interface GetCategoriesResponse {
    success: boolean
    data: Category[]
}

const toast = useToast()

const schema = z.object({
    title: z.string().min(1, 'Заголовок обов\'язковий').max(255, 'Заголовок занадто довгий'),
    category_id: z.number().min(1, 'Категорія обов\'язкова'),
    excerpt: z.string().max(500, 'Витяжка занадто довга').optional(),
    content_raw: z.string().min(1, 'Контент обов\'язковий'),
    is_published: z.boolean().optional()
})

type Schema = z.output<typeof schema>

const state = reactive({
    title: '',
    category_id: 0,
    excerpt: '',
    content_raw: '',
    is_published: false
})

const { data: categories, status: categoriesStatus } = await useFetch<GetCategoriesResponse>('http://localhost/api/blog/categories', {
    key: 'categories-for-posts',
    transform: (data) => {
        const categories = data?.data || []
        return categories.map(cat => ({
            label: cat.title,
            value: cat.id
        }))
    },
    lazy: true
})

const isSubmitting = ref(false)

const selectedCategory = ref(null)

watch(selectedCategory, (newValue) => {
    if (newValue && typeof newValue === 'object' && 'value' in newValue) {
        state.category_id = newValue.value
    } else {
        state.category_id = 0
    }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
    isSubmitting.value = true

    try {
        const { success, message } = await $fetch('http://localhost/api/blog/posts', {
            method: 'POST',
            body: {
                title: event.data.title,
                category_id: event.data.category_id,
                excerpt: event.data.excerpt || null,
                content_raw: event.data.content_raw,
                is_published: event.data.is_published || false
            }
        })

        if (success) {
            toast.add({
                title: 'Успіх',
                description: message || 'Пост успішно створено',
                color: 'success',
                icon: 'i-heroicons-check-circle'
            })
            await navigateTo('/BlogPostsUi')
        }
    } catch (error: any) {
        toast.add({
            title: 'Помилка',
            description: error.data?.message || 'Не вдалося створити пост',
            color: 'error',
            icon: 'i-heroicons-x-circle'
        })
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="container mx-auto py-8 px-4 max-w-4xl">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <UButton
                    to="/BlogPostsUi"
                    color="gray"
                    variant="ghost"
                    icon="i-heroicons-arrow-left"
                    size="sm"
                >
                    Назад
                </UButton>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Створити пост
                </h1>
            </div>

            <UCard>
                <UForm :schema="schema" :state="state" class="space-y-6" @submit="onSubmit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <UFormField label="Заголовок поста" name="title" required>
                            <UInput
                                v-model="state.title"
                                placeholder="Введіть заголовок поста"
                                :disabled="isSubmitting"
                            />
                        </UFormField>

                        <UFormField label="Категорія" name="category_id" required>
                            <USelectMenu
                                v-model="selectedCategory"
                                :items="categories"
                                :loading="categoriesStatus === 'pending'"
                                placeholder="Оберіть категорію"
                                :disabled="isSubmitting"
                            />
                        </UFormField>
                    </div>

                    <UFormField
                        label="Короткий опис"
                        name="excerpt"
                        description="Короткий опис поста для превью (необов'язково)"
                    >
                        <UTextarea
                            v-model="state.excerpt"
                            placeholder="Введіть короткий опис поста"
                            :rows="3"
                            :disabled="isSubmitting"
                        />
                    </UFormField>

                    <UFormField label="Контент поста" name="content_raw" required>
                        <UTextarea
                            v-model="state.content_raw"
                            placeholder="Введіть контент поста"
                            :rows="10"
                            :disabled="isSubmitting"
                        />
                    </UFormField>

                    <UFormField name="is_published">
                        <UCheckbox
                            v-model="state.is_published"
                            label="Опублікувати пост"
                            :disabled="isSubmitting"
                        />
                    </UFormField>

                    <div class="flex gap-3 justify-end">
                        <UButton
                            type="button"
                            color="gray"
                            variant="solid"
                            @click="navigateTo('/posts')"
                            :disabled="isSubmitting"
                        >
                            Скасувати
                        </UButton>
                        <UButton
                            type="submit"
                            color="primary"
                            variant="solid"
                            :loading="isSubmitting"
                        >
                            Створити пост
                        </UButton>
                    </div>
                </UForm>
            </UCard>
        </div>
    </div>
</template>
