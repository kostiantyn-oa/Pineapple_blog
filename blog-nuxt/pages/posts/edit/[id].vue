<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

interface Category {
    id: number
    title: string
}

interface User {
    id: number
    name: string
}

interface Post {
    id: number
    title: string
    category_id: number
    excerpt: string | null
    content_raw: string
    is_published: boolean
    published_at: string | null
    category: Category
    user: User
}

interface GetPostResponse {
    success: boolean
    data: Post
}

interface GetCategoriesResponse {
    success: boolean
    data: Category[]
}

const route = useRoute()
const toast = useToast()
const postId = parseInt(route.params.id as string)

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

const { data: post, status: postStatus } = await useFetch<GetPostResponse>(`http://localhost/api/blog/posts/${postId}`, {
    key: `post-${postId}`,
    transform: (data) => data?.data,
    lazy: true
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

const selectedCategory = ref(null)

watch(selectedCategory, (newValue) => {
    if (newValue && typeof newValue === 'object' && 'value' in newValue) {
        state.category_id = newValue.value
    } else {
        state.category_id = 0
    }
})

watch(post, (newPost) => {
    if (newPost) {
        state.title = newPost.title
        state.category_id = newPost.category_id

        const category = categories.value?.find(cat => cat.value === newPost.category_id)
        selectedCategory.value = category || null

        state.excerpt = newPost.excerpt || ''
        state.content_raw = newPost.content_raw
        state.is_published = newPost.is_published
    }
}, { immediate: true })

watch(categories, (newCategories) => {
    if (newCategories && post.value) {
        const category = newCategories.find(cat => cat.value === post.value.category_id)
        selectedCategory.value = category || null
    }
}, { immediate: true })

const isSubmitting = ref(false)

async function onSubmit(event: FormSubmitEvent<Schema>) {
    isSubmitting.value = true

    try {
        const { success, message } = await $fetch(`http://localhost/api/blog/posts/${postId}`, {
            method: 'PUT',
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
                description: message || 'Пост успішно оновлено',
                color: 'success',
                icon: 'i-heroicons-check-circle'
            })
            await navigateTo('/BlogPostsUi')
        }
    } catch (error: any) {
        toast.add({
            title: 'Помилка',
            description: error.data?.message || 'Невдалося оновити пост',
            color: 'error',
            icon: 'i-heroicons-x-circle'
        })
    } finally {
        isSubmitting.value = false
    }
}

if (postStatus.value === 'success' && !post.value) {
    throw createError({
        statusCode: 404,
        statusMessage: 'Пост не знайдено'
    })
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
                    Редагувати пост
                </h1>
            </div>

            <UCard v-if="post">
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
                        label="Короткий опис (витяжка)"
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
                            Оновити пост
                        </UButton>
                    </div>
                </UForm>
            </UCard>

            <UCard v-else-if="postStatus === 'pending'">
                <div class="flex justify-center py-8">
                    <UIcon name="i-heroicons-arrow-path" class="animate-spin text-2xl" />
                </div>
            </UCard>
        </div>
    </div>
</template>
