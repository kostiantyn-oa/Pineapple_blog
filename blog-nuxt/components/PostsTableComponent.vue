<template>
    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="space-y-6">
            <header class="bg-emerald-100 border border-emerald-300 rounded-md px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-emerald-800">Усі пости</h2>
                <a href="http://localhost/admin/blog/posts/create"
                   class="bg-emerald-600 text-white text-sm font-medium px-4 py-2 rounded-md shadow hover:bg-emerald-700 transition">
                    + Додати пост
                </a>
            </header>

            <div class="bg-white border border-gray-200 rounded-md shadow-md overflow-hidden">
                <div class="p-4">
                    <div v-if="loading" class="text-center text-gray-600">
                        <p>Завантаження даних...</p>
                    </div>

                    <div v-else-if="error" class="text-center text-red-600 font-medium">
                        <p>Помилка: {{ error }}</p>
                    </div>

                    <table v-else class="min-w-full table-auto text-sm border-separate border-spacing-y-2">
                        <thead>
                        <tr class="bg-emerald-50 text-emerald-800 uppercase text-xs tracking-wider rounded-md">
                            <th class="py-3 px-5 text-left rounded-tl-md">№</th>
                            <th class="py-3 px-5 text-left">Заголовок</th>
                            <th class="py-3 px-5 text-left">Автор</th>
                            <th class="py-3 px-5 text-left">Категорія</th>
                            <th class="py-3 px-5 text-left rounded-tr-md">Дата публікації</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="post in posts"
                            :key="post.id"
                            class="bg-white shadow-sm border border-gray-200 rounded-md hover:bg-emerald-50 transition"
                        >
                            <td class="py-3 px-5 text-gray-700 font-medium">{{ post.id }}</td>
                            <td class="py-3 px-5 text-emerald-700 font-semibold break-words">
                                <a
                                    :href="'http://localhost/admin/blog/posts/' + post.id + '/edit'"
                                    class="hover:underline hover:text-emerald-900 transition"
                                >
                                    {{ post.title }}
                                </a>
                            </td>
                            <td class="py-3 px-5 text-gray-700">{{ post.user?.name || 'Невідомий автор' }}</td>
                            <td class="py-3 px-5 text-gray-700">{{ post.category?.title || 'Без категорії' }}</td>
                            <td class="py-3 px-5 text-gray-500">
                                {{ formatDate(post.published_at) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface User {
    id: number;
    name: string;
}

interface Category {
    id: number;
    title: string;
}

interface Post {
    id: number;
    title: string;
    published_at: string;
    user: User;
    category: Category;
}

interface GetPostsResponse {
    success: boolean;
    data: Post[];
}

const posts = ref<Post[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const formatDate = (dateString: string) => {
    return dateString
        ? new Date(dateString).toLocaleDateString('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric' })
        : '-';
};

const getPosts = async () => {
    loading.value = true;
    error.value = null;

    try {
        const { success, data } = await $fetch<GetPostsResponse>('/api/blog/posts');

        if (!success) {
            error.value = 'Помилка отримання даних!';
            return;
        }

        posts.value = data;
    } catch (err) {
        console.error('Помилка під час отримання постів:', err);

        if (err instanceof Error) {
            error.value = err.message || 'Помилка зʼєднання з сервером!';
        } else {
            error.value = 'Невідома помилка!';
        }
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await getPosts();
});
</script>
