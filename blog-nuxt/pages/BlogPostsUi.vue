<script setup lang="ts">
import { getPaginationRowModel } from '@tanstack/vue-table'
import type { TableColumn, DropdownMenuItem } from '@nuxt/ui'

interface User {
    id: number
    name: string
}

interface Category {
    id: number
    title: string
}

interface Post {
    id: number
    title: string
    published_at: string | null
    user: User | null
    category: Category | null
}

interface GetPostsResponse {
    success: boolean
    data: Post[]
}

const table = useTemplateRef('table')
const toast = useToast()

const { data: posts, status, refresh } = await useFetch<GetPostsResponse>('http://localhost/api/blog/posts', {
    key: 'blog-posts',
    transform: (data) => {
        return data?.data || []
    },
    lazy: true
})

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const deletePost = async (postId: number) => {
    try {
        const { success, message } = await $fetch(`http://localhost/api/blog/posts/${postId}`, {
            method: 'DELETE'
        })

        if (success) {
            toast.add({
                title: 'Успіх',
                description: message || 'Пост успішно видалено',
                color: 'success',
                icon: 'i-heroicons-check-circle'
            })
            await refresh()
        }
    } catch (error) {
        toast.add({
            title: 'Помилка',
            description: 'Не вдалося видалити пост',
            color: 'error',
            icon: 'i-heroicons-x-circle'
        })
    }
}

const getDropdownActions = (post: Post): DropdownMenuItem[][] => {
    return [
        [
            {
                label: 'Редагувати',
                icon: 'i-heroicons-pencil-square',
                onSelect: () => {
                    navigateTo(`/posts/edit/${post.id}`)
                }
            }
        ],
        [
            {
                label: 'Видалити',
                icon: 'i-heroicons-trash',
                color: 'error',
                onSelect: () => {
                    deletePost(post.id)
                }
            }
        ]
    ]
}

const columns: TableColumn<Post>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
        cell: ({ row }) => `#${row.getValue('id')}`
    },
    {
        accessorKey: 'title',
        header: 'Заголовок',
        cell: ({ row }) => {
            const post = row.original
            return h(resolveComponent('NuxtLink'), {
                to: `/posts/${post.id}`,
                class: 'text-primary hover:underline font-medium hover:text-primary-600 transition-colors'
            }, () => post.title)
        }
    },
    {
        accessorKey: 'user',
        header: 'Автор',
        cell: ({ row }) => {
            const user = row.original.user
            return user?.name || 'Невідомий автор'
        }
    },
    {
        accessorKey: 'category',
        header: 'Категорія',
        cell: ({ row }) => {
            const category = row.original.category
            return category?.title || 'Без категорії'
        }
    },
    {
        accessorKey: 'published_at',
        header: 'Дата публікації',
        cell: ({ row }) => {
            return formatDate(row.getValue('published_at'))
        }
    },
    {
        id: 'actions',
        header: 'Дії'
    }
]

const pagination = ref({
    pageIndex: 0,
    pageSize: 10
})
</script>

<template>
    <div class="container mx-auto py-8 px-4">
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Усі пости
                </h1>
                <UButton
                    to="/posts/create"
                    color="primary"
                    variant="solid"
                    size="md"
                >
                    <template #leading>
                        <UIcon name="i-heroicons-plus" />
                    </template>
                    Додати пост
                </UButton>
            </div>

            <div class="space-y-4">
                <UTable
                    ref="table"
                    v-model:pagination="pagination"
                    :data="posts"
                    :columns="columns"
                    :loading="status === 'pending'"
                    :pagination-options="{
                        getPaginationRowModel: getPaginationRowModel()
                    }"
                    class="flex-1"
                >
                    <template #actions-cell="{ row }">
                        <UDropdownMenu :items="getDropdownActions(row.original)">
                            <UButton
                                icon="i-heroicons-ellipsis-vertical"
                                color="neutral"
                                variant="ghost"
                                aria-label="Дії"
                            />
                        </UDropdownMenu>
                    </template>
                </UTable>

                <div class="flex justify-center border-t border-gray-200 dark:border-gray-800 pt-4">
                    <UPagination
                        :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
                        :items-per-page="table?.tableApi?.getState().pagination.pageSize"
                        :total="table?.tableApi?.getFilteredRowModel().rows.length"
                        @update:page="(p) => table?.tableApi?.setPageIndex(p - 1)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
