import type { Preview } from '@storybook/react-vite';
import { ColorModeProvider } from '../src/shared/theme/ColorModeProvider';
import type { ColorMode } from '../src/shared/theme/theme';

// Storybook usa EXACTAMENTE el mismo ColorModeProvider (y createAppTheme) que la app
// (storybook-spec.md §2): el toggle del toolbar monta el provider real en claro/oscuro.
const preview: Preview = {
  parameters: {
    controls: { matchers: { color: /(background|color)$/i, date: /Date$/i } },
    a11y: { test: 'todo' },
  },
  globalTypes: {
    theme: {
      description: 'Tema claro / oscuro',
      toolbar: {
        title: 'Tema',
        icon: 'paintbrush',
        items: [
          { value: 'light', title: 'Claro' },
          { value: 'dark', title: 'Oscuro' },
        ],
        dynamicTitle: true,
      },
    },
  },
  initialGlobals: { theme: 'light' },
  decorators: [
    (Story, context) => {
      const mode = (context.globals.theme as ColorMode | undefined) ?? 'light';
      return (
        <ColorModeProvider key={mode} initialMode={mode}>
          <Story />
        </ColorModeProvider>
      );
    },
  ],
  tags: ['autodocs'],
};

export default preview;
