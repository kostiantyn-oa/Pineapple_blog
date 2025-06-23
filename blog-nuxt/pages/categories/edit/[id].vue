<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

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
}

interface GetCategoryResponse {
    success: boolean
    data: Category
}

interface GetParentCategoriesResponse {
    success: boolean
    data: ParentCategory[]
}

const route = useRoute()
const toast = useToast()
const categoryId = parseInt(route.params.id as string)

const schema = z.object({
    title: z.string().min(1, 'Назва обов\'язкова').max(255, 'Назва занадто довга'),
    parent_id: z.number().optional(),
    description: z.string().max(1000, 'Опис занадто довгий').optional()
})

type Schema = z.output<typeof schema>

const state = reactive({
    title: '',
    parent_id: undefined as number | undefined,
    description: ''
})

const { data: category, status: categoryStatus } = await useFetch<GetCategoryResponse>(`http://localhost/api/blog/categories/${categoryId}`, {
    key: `category-${categoryId}`,
    transform: (data) => data?.data,
    lazy: true
})

const { data: parentCategories, status: parentCategoriesStatus } = await useFetch<GetParentCategoriesResponse>('http://localhost/api/blog/categories/parent-categories', {
    key: 'parent-categories',
    transform: (data) => {
        const categories = data?.data || []
        return categories
            .filter(cat => cat.id !== categoryId)
            .map(cat => ({
                label: cat.title,
                value: cat.id
            }))
    },
    lazy: true
})

const selectedParentCategory = ref(null)

watch(selectedParentCategory, (newValue) => {
    if (newValue && typeof newValue === 'object' && 'value' in newValue) {
        state.parent_id = newValue.value
    } else {
        state.parent_id = undefined
    }
})

watch(category, (newCategory) => {
    if (newCategory) {
        state.title = newCategory.title
        const parentId = newCategory.parent_id === 1 ? undefined : newCategory.parent_id
        state.parent_id = parentId

        if (parentId) {
            const parentCat = parentCategories.value?.find(cat => cat.value === parentId)
            selectedParentCategory.value = parentCat || null
        } else {
            selectedParentCategory.value = null
        }

        state.description = newCategory.description || ''
    }
}, { immediate: true })

watch(parentCategories, (newParentCategories) => {
    if (newParentCategories && category.value) {
        const parentId = category.value.parent_id === 1 ? undefined : category.value.parent_id
        if (parentId) {
            const parentCat = newParentCategories.find(cat => cat.value === parentId)
            selectedParentCategory.value = parentCat || null
        }
    }
}, { immediate: true })

const isSubmitting = ref(false)

async function onSubmit(event: FormSubmitEvent<Schema>) {
    isSubmitting.value = true

    try {
        const { success, message } = await $fetch(`http://localhost/api/blog/categories/${categoryId}`, {
            method: 'PUT',
            body: {
                title: event.data.title,
                parent_id: event.data.parent_id,
                description: event.data.description || null
            }
        })

        if (success) {
            toast.add({
                title: 'Успіх',
                description: message || 'Категорію успішно оновлено',
                color: 'success',
                icon: 'i-heroicons-check-circle'
            })
            await navigateTo('/categories')
        }
    } catch (error: any) {
        toast.add({
            title: 'Помилка',
            description: error.data?.message || 'Не вдалося оновити категорію',
            color: 'error',
            icon: 'i-heroicons-x-circle'
        })
    } finally {
        isSubmitting.value = false
    }
}

if (categoryStatus.value === 'success' && !category.value) {
    throw createError({
        statusCode: 404,
        statusMessage: 'Категорію не знайдено'
    })
}
</script>

<template>
    <div class="container mx-auto py-8 px-4 max-w-2xl">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <UButton
                    to="/categories"
                    color="gray"
                    variant="ghost"
                    icon="i-heroicons-arrow-left"
                    size="sm"
                >
                    Назад
                </UButton>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Редагувати категорію
                </h1>
            </div>

            <UCard v-if="category">
                <UForm :schema="schema" :state="state" class="space-y-6" @submit="onSubmit">
                    <UFormField label="Назва категорії" name="title" required>
                        <UInput
                            v-model="state.title"
                            placeholder="Введіть назву категорії"
                            :disabled="isSubmitting"
                        />
                    </UFormField>

                    <UFormField
                        label="Батьківська категорія"
                        name="parent_id"
                        description="Оберіть батьківську категорію або залиште порожнім для кореневої категорії"
                    >
                        <USelectMenu
                            v-model="selectedParentCategory"
                            :items="parentCategories"
                            :loading="parentCategoriesStatus === 'pending'"
                            placeholder="Оберіть батьківську категорію"
                            :disabled="isSubmitting"
                            clearable
                        />
                    </UFormField>

                    <UFormField
                        label="Опис"
                        name="description"
                        description="Короткий опис категорії (необов'язково)"
                    >
                        <UTextarea
                            v-model="state.description"
                            placeholder="Введіть опис категорії"
                            :rows="4"
                            :disabled="isSubmitting"
                        />
                    </UFormField>

                    <div class="flex gap-3 justify-end">
                        <UButton
                            type="button"
                            color="gray"
                            variant="solid"
                            @click="navigateTo('/categories')"
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
                            Оновити категорію
                        </UButton>
                    </div>
                </UForm>
            </UCard>

            <UCard v-else-if="categoryStatus === 'pending'">
                <div class="flex justify-center py-8">
                    <UIcon name="i-heroicons-arrow-path" class="animate-spin text-2xl" />
                </div>
            </UCard>
        </div>
    </div>
</template>
