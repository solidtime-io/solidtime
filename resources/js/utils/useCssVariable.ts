import { ref, onMounted, onUnmounted } from 'vue'

export function useCssVariable(variableName: string) {
  const value = ref('')
  let observer: MutationObserver | null = null
  let mediaQuery: MediaQueryList | null = null
  
  const updateValue = () => {
    const computedStyle = getComputedStyle(document.documentElement)
    const cssValue = computedStyle.getPropertyValue(variableName).trim()
    value.value = cssValue
  }
  
  onMounted(() => {
    // Initialize with current value
    updateValue()
    
    // Watch for class changes on document.documentElement (where theme classes are applied)
    observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          updateValue()
        }
      })
    })
    
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class']
    })
    
    // Also watch for system color scheme changes
    if (window.matchMedia) {
      mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      mediaQuery.addEventListener('change', updateValue)
    }
  })
  
  onUnmounted(() => {
    if (observer) {
      observer.disconnect()
    }
    if (mediaQuery) {
      mediaQuery.removeEventListener('change', updateValue)
    }
  })
  
  return value
} 