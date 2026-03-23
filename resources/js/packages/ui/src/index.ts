declare global {
    interface Window {
        getWeekStartSetting: () => string;
        getTimezoneSetting: () => string;
    }
}

import * as color from './utils/color';
import * as money from './utils/money';
import * as random from './utils/random';
import * as time from './utils/time';

export { buttonVariants, type ButtonVariants } from './Buttons/index';
export type {
    CommandPaletteCommand,
    CommandPaletteGroup,
    EntitySearchResult,
} from './CommandPalette/index';
export type { FieldVariants } from './field/index';
export type { CalendarSettings } from './FullCalendar/calendarSettings';
export type { ActivityPeriod } from './FullCalendar/activityTypes';
export { cn } from './utils/cn';
export { useCssVariable } from './utils/useCssVariable';

import Badge from './Badge.vue';
import Button from './Buttons/Button.vue';
import PrimaryButton from './Buttons/PrimaryButton.vue';
import SecondaryButton from './Buttons/SecondaryButton.vue';
import CardTitle from './CardTitle.vue';
import Checkbox from './Input/Checkbox.vue';
import InputLabel from './Input/InputLabel.vue';
import TextInput from './Input/TextInput.vue';
import LoadingSpinner from './LoadingSpinner.vue';
import Modal from './Modal.vue';
import ProjectBadge from './Project/ProjectBadge.vue';
import TimeEntryCreateModal from './TimeEntry/TimeEntryCreateModal.vue';
import TimeEntryEditModal from './TimeEntry/TimeEntryEditModal.vue';
import TimeEntryGroupedTable from './TimeEntry/TimeEntryGroupedTable.vue';
import TimeEntryMassActionRow from './TimeEntry/TimeEntryMassActionRow.vue';
import TimeTrackerControls from './TimeTracker/TimeTrackerControls.vue';
import TimeTrackerMoreOptionsDropdown from './TimeTracker/TimeTrackerMoreOptionsDropdown.vue';
import TimeTrackerRunningInDifferentOrganizationOverlay from './TimeTracker/TimeTrackerRunningInDifferentOrganizationOverlay.vue';
import TimeTrackerStartStop from './TimeTrackerStartStop.vue';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from './accordion/index';
import {
    Calendar,
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNextButton,
    CalendarPrevButton,
} from './calendar/index';
import { CommandPalette } from './CommandPalette/index';
import {
    ContextMenu,
    ContextMenuCheckboxItem,
    ContextMenuContent,
    ContextMenuGroup,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuRadioGroup,
    ContextMenuRadioItem,
    ContextMenuSeparator,
    ContextMenuShortcut,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from './context-menu/index';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogTrigger,
} from './dialog/index';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from './dropdown-menu/index';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    FieldTitle,
    fieldVariants,
} from './field/index';
import CalendarSettingsPopover from './FullCalendar/CalendarSettingsPopover.vue';
import CalendarToolbar from './FullCalendar/CalendarToolbar.vue';
import FullCalendarDayHeader from './FullCalendar/FullCalendarDayHeader.vue';
import FullCalendarEventContent from './FullCalendar/FullCalendarEventContent.vue';
import TimeEntryCalendar from './FullCalendar/TimeEntryCalendar.vue';
import DateRangePicker from './Input/DateRangePicker.vue';
import { Label } from './label/index';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from './number-field/index';
import { Popover, PopoverAnchor, PopoverContent, PopoverTrigger } from './popover/index';
import { Progress } from './progress/index';
import { RangeCalendar } from './range-calendar/index';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectItemText,
    SelectLabel,
    SelectScrollDownButton,
    SelectScrollUpButton,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from './select/index';
import { Separator } from './separator/index';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './tabs/index';
import TabBar from './TabBar/TabBar.vue';
import TabBarItem from './TabBar/TabBarItem.vue';
import TimezoneMismatchModal from './TimezoneMismatchModal.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from './tooltip/index';

export {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
    Badge,
    Button,
    Calendar,
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNextButton,
    CalendarPrevButton,
    CalendarSettingsPopover,
    CalendarToolbar,
    CardTitle,
    Checkbox,
    color,
    CommandPalette,
    ContextMenu,
    ContextMenuCheckboxItem,
    ContextMenuContent,
    ContextMenuGroup,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuRadioGroup,
    ContextMenuRadioItem,
    ContextMenuSeparator,
    ContextMenuShortcut,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
    DateRangePicker,
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogTrigger,
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    FieldTitle,
    fieldVariants,
    FullCalendarDayHeader,
    FullCalendarEventContent,
    InputLabel,
    Label,
    LoadingSpinner,
    Modal,
    money,
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
    Popover,
    PopoverAnchor,
    PopoverContent,
    PopoverTrigger,
    PrimaryButton,
    Progress,
    ProjectBadge,
    random,
    RangeCalendar,
    SecondaryButton,
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectItemText,
    SelectLabel,
    SelectScrollDownButton,
    SelectScrollUpButton,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
    Separator,
    TabBar,
    TabBarItem,
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
    TextInput,
    time,
    TimeEntryCalendar,
    TimeEntryCreateModal,
    TimeEntryEditModal,
    TimeEntryGroupedTable,
    TimeEntryMassActionRow,
    TimeTrackerControls,
    TimeTrackerMoreOptionsDropdown,
    TimeTrackerRunningInDifferentOrganizationOverlay,
    TimeTrackerStartStop,
    TimezoneMismatchModal,
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
};
