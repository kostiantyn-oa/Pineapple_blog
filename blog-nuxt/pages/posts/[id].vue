<script setup lang="ts">
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
    excerpt: string | null
    content_raw: string
    is_published: boolean
    published_at: string | null
    created_at: string
    updated_at: string
    category: Category
    user: User
}

interface GetPostResponse {
    success: boolean
    data: Post
}

const route = useRoute()
const postId = parseInt(route.params.id as string)

const { data: post, status } = await useFetch<GetPostResponse>(`http://localhost/api/blog/posts/${postId}`, {
    key: `post-view-${postId}`,
    transform: (data) => data?.data,
    lazy: true
})

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

if (status.value === 'success' && !post.value) {
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
                    Назад до списку
                </UButton>
            </div>

            <UCard v-if="post">
                <div class="space-y-6">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ post.title }}
                        </h1>

                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <UIcon name="i-heroicons-user" />
                                <span>{{ post.user?.name || 'Невідомий автор' }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <UIcon name="i-heroicons-folder" />
                                <span>{{ post.category?.title || 'Без категорії' }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <UIcon name="i-heroicons-calendar" />
                                <span>{{ formatDate(post.published_at || post.created_at) }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <UIcon :name="post.is_published ? 'i-heroicons-eye' : 'i-heroicons-eye-slash'" />
                                <span>{{ post.is_published ? 'Опубліковано' : 'Чернетка' }}</span>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <UButton
                                :to="`/posts/edit/${post.id}`"
                                color="primary"
                                variant="outline"
                                size="sm"
                                icon="i-heroicons-pencil-square"
                            >
                                Редагувати
                            </UButton>
                        </div>
                    </div>

                    <div v-if="post.excerpt" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Короткий опис
                        </h2>
                        <p class="text-gray-700 dark:text-gray-300">
                            {{ post.excerpt }}
                        </p>
                    </div>

                    <div class="prose prose-gray dark:prose-invert max-w-none">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Контент
                        </h2>
                        <div class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ post.content_raw }}
                        </div>
                    </div>
                </div>
            </UCard>

            <UCard v-else-if="status === 'pending'">
                <div class="flex justify-center py-8">
                    <UIcon name="i-heroicons-arrow-path" class="animate-spin text-2xl" />
                </div>
            </UCard>
        </div>
    </div>
</template>
