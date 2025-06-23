<script setup lang="ts">
import { getPaginationRowModel } from '@tanstack/vue-table'
import type { TableColumn, DropdownMenuItem } from '@nuxt/ui'

interface ParentCategory {
    id: number
    title: string
}

interface Category {
    id: number
    title: string
    description: string | null
    parent_id: number
    parent_category: ParentCategory | null
    created_at: string | null
    updated_at: string | null
}

interface GetCategoriesResponse {
    success: boolean
    data: Category[]
}

const table = useTemplateRef('table')
const toast = useToast()

const { data: categories, status, refresh } = await useFetch<GetCategoriesResponse>('http://localhost/api/blog/categories', {
    key: 'blog-categories',
    transform: (data) => {
        return data?.data || []
    },
    lazy: true
})

const deleteCategory = async (categoryId: number) => {
    try {
        const { success, message } = await $fetch(`http://localhost/api/blog/categories/${categoryId}`, {
            method: 'DELETE'
        })

        if (success) {
            toast.add({
                title: 'Успіх',
                description: message || 'Категорію успішно видалено',
                color: 'success',
                icon: 'i-heroicons-check-circle'
            })
            await refresh()
        }
    } catch (error: any) {
        toast.add({
            title: 'Помилка',
            description: error.data?.message || 'Не вдалося видалити категорію',
            color: 'error',
            icon: 'i-heroicons-x-circle'
        })
    }
}

const getDropdownActions = (category: Category): DropdownMenuItem[][] => {
    return [
        [
            {
                label: 'Редагувати',
                icon: 'i-heroicons-pencil-square',
                onSelect: () => {
                    navigateTo(`/categories/edit/${category.id}`)
                }
            }
        ],
        [
            {
                label: 'Видалити',
                icon: 'i-heroicons-trash',
                color: 'error',
                onSelect: () => {
                    deleteCategory(category.id)
                }
            }
        ]
    ]
}

const columns: TableColumn<Category>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
        cell: ({ row }) => `#${row.getValue('id')}`
    },
    {
        accessorKey: 'title',
        header: 'Назва',
        cell: ({ row }) => {
            const category = row.original
            return h('span', { class: 'font-medium' }, category.title)
        }
    },
    {
        accessorKey: 'parent_category',
        header: 'Батьківська категорія',
        cell: ({ row }) => {
            const parentCategory = row.original.parent_category
            return parentCategory?.title || 'Корінь'
        }
    },
    {
        accessorKey: 'description',
        header: 'Опис',
        cell: ({ row }) => {
            const description = row.getValue('description') as string | null
            return description || '-'
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
                    Усі категорії
                </h1>
                <UButton
                    to="/categories/create"
                    color="primary"
                    variant="solid"
                    size="md"
                >
                    <template #leading>
                        <UIcon name="i-heroicons-plus" />
                    </template>
                    Додати категорію
                </UButton>
            </div>

            <div class="space-y-4">
                <UTable
                    ref="table"
                    v-model:pagination="pagination"
                    :data="categories"
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
