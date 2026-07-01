import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import HelloWorld from '@/components/HelloWorld.vue'

describe('HelloWorld', () => {
    it('renders default message', () => {
        const wrapper = mount(HelloWorld)
        expect(wrapper.text()).toContain('Vue 3 + TypeScript + Laravel')
    })

    it('renders custom message', () => {
        const wrapper = mount(HelloWorld, {
            props: { msg: 'Custom Title' },
        })
        expect(wrapper.text()).toContain('Custom Title')
    })
})
