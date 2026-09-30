import { ref } from 'vue';

export const searchQuery = ref('');
export const selectedLocation = ref('');
export const selectedCategory = ref('');

export function setSearchQuery(q) {
    searchQuery.value = q;
}

export function setSelectedLocation(loc) {
    selectedLocation.value = loc;
}

export function setSelectedCategory(cat) {
    selectedCategory.value = cat;
}

export function resetSearch() {
    searchQuery.value = '';
    selectedLocation.value = '';
    selectedCategory.value = '';
}
