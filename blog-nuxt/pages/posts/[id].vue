<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4 sm:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <UButton
                    to="/BlogPostsUi"
                    variant="solid"
                    color="primary"
                    size="lg"
                    class="shadow-md hover:shadow-lg transition-shadow"
                >
                    <template #leading>
                        <UIcon name="i-heroicons-arrow-left" />
                    </template>
                    Назад до таблиці
                </UButton>
            </div>
            <div v-if="pending" class="flex justify-center items-center py-20">
                <div class="text-center">
                    <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary mb-4" />
                    <p class="text-gray-600">Завантаження посту...</p>
                </div>
            </div>
            <div v-else-if="error" class="text-center py-20">
                <UAlert
                    color="red"
                    variant="soft"
                    title="Помилка"
                    :description="error.message || 'Не вдалося завантажити пост'"
                />
            </div>

            <div v-else-if="post" class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-4 leading-tight">
                                {{ post.title }}
                            </h1>
                            <!-- Метадані -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <UIcon name="i-heroicons-user" class="w-4 h-4" />
                                    <span>{{ post.user?.name || 'Невідомий автор' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <UIcon name="i-heroicons-tag" class="w-4 h-4" />
                                    <span>{{ post.category?.title || 'Без категорії' }}</span>
                                </div>
                                <div v-if="post.published_at" class="flex items-center gap-2">
                                    <UIcon name="i-heroicons-calendar" class="w-4 h-4" />
                                    <span>{{ formatDate(post.published_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="ml-4">
                            <UBadge
                                :color="post.is_published ? 'green' : 'yellow'"
                                variant="soft"
                                size="md"
                            >
                                {{ post.is_published ? 'Опубліковано' : 'Чернетка' }}
                            </UBadge>
                        </div>
                    </div>
                    <div v-if="post.excerpt" class="bg-gray-50 rounded-lg p-4 border-l-4 border-primary">
                        <p class="text-gray-700 italic text-lg leading-relaxed">
                            {{ post.excerpt }}
                        </p>
                    </div>
                </div>
                <div class="px-8 py-8">
                    <div class="prose prose-lg max-w-none">
                        <div
                            v-if="post.content_html"
                            v-html="post.content_html"
                            class="text-gray-800 leading-relaxed"
                        />
                        <div v-else-if="post.content_raw" class="whitespace-pre-wrap text-gray-800 leading-relaxed">
                            {{ post.content_raw }}
                        </div>
                        <div v-else class="text-gray-500 italic text-center py-8">
                            Контент посту відсутній
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div>
                            <span class="font-medium">ID посту:</span> #{{ post.id }}
                        </div>
                        <div v-if="post.slug">
                            <span class="font-medium">Slug:</span>
                            <code class="bg-gray-200 px-2 py-1 rounded text-xs">{{ post.slug }}</code>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-20">
                <UAlert
                    color="amber"
                    variant="soft"
                    title="Пост не знайдено"
                    description="Запитуваний пост не існує або був видалений"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface User {
    id: number
    name: string
    email: string
    profile_photo_url: string
}
interface Category {
    id: number
    title: string
    slug: string
    description?: string
}
interface Post {
    id: number
    title: string
    slug: string
    excerpt?: string
    content_raw?: string
    content_html?: string
    is_published: number
    published_at: string | null
    created_at: string
    updated_at: string
    user: User | null
    category: Category | null
}
interface GetPostResponse {
    success: boolean
    data: Post
    message?: string
}

const route = useRoute()
const postId = route.params.id

useHead({
    title: 'Перегляд посту'
})

const { data: postData, pending, error } = await useFetch<GetPostResponse>(`/api/blog/posts/${postId}`, {
    lazy: true,
    server: false
})

const post = computed(() => {
    if (postData.value?.success && postData.value?.data) {
        return postData.value.data
    }
    return null
})

watch(post, (newPost) => {
    if (newPost) {
        useHead({
            title: `${newPost.title} - Блог`
        })
    }
}, { immediate: true })

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('uk-UA', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

if (error.value?.statusCode === 404) {
    throw createError({
        statusCode: 404,
        statusMessage: 'Пост не знайдено'
    })
}
</script>
