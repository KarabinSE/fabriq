import FButton from '@fabriq/components/forms/FButton.vue'
import FButtonItem from '@fabriq/components/forms/FButtonItem.vue'
import FButtonList from '@fabriq/components/forms/FButtonList.vue'
import FButtonSwitch from '@fabriq/components/forms/FButtonSwitch.vue'
import FCommentEditor from '@fabriq/components/forms/FCommentEditor.vue'
import FConfirm from '@fabriq/components/forms/FConfirm.vue'
import FConfirmDropdown from '@fabriq/components/forms/FConfirmDropdown.vue'
import FDatePicker from '@fabriq/components/forms/FDatePicker.vue'
import FEditor from '@fabriq/components/forms/FEditor.vue'
import FFileInput from '@fabriq/components/forms/FFileInput.vue'
import FImageInput from '@fabriq/components/forms/FImageInput.vue'
import FInput from '@fabriq/components/forms/FInput.vue'
import FLabel from '@fabriq/components/forms/FLabel.vue'
import FLocaleSelect from '@fabriq/components/forms/FLocaleSelect.vue'
import FMediaPicker from '@fabriq/components/forms/FMediaPicker.vue'
import FModal from '@fabriq/components/forms/FModal.vue'
import FSearchInput from '@fabriq/components/forms/FSearchInput.vue'
import FSelect from '@fabriq/components/forms/FSelect.vue'
import FSwitch from '@fabriq/components/forms/FSwitch.vue'
import FUpload from '@fabriq/components/forms/FUpload.vue'
import FVideoInput from '@fabriq/components/forms/FVideoInput.vue'
import HelpText from '@fabriq/components/forms/HelpText.vue'
import FTab from '@fabriq/components/forms/tabs/FTab.vue'
import FTabs from '@fabriq/components/forms/tabs/FTabs.vue'
import UiLogo from '@fabriq/components/Logo.vue'
import CreateModal from '@fabriq/components/modals/CreateModal.vue'
import FTable from '@fabriq/components/table/FTable.vue'
import UiAvatar from '@fabriq/components/ui/UiAvatar.vue'
import UiBadge from '@fabriq/components/ui/UiBadge.vue'
import UiCard from '@fabriq/components/ui/UiCard.vue'
import UiDashedBox from '@fabriq/components/ui/UiDashedBox.vue'
import UiDropdown from '@fabriq/components/ui/UiDropdown.vue'
import UiImagePresenter from '@fabriq/components/ui/UiImagePresenter.vue'
import UiSectionHeader from '@fabriq/components/ui/UiSectionHeader.vue'
import UiStatsCard from '@fabriq/components/ui/UiStatsCard.vue'
import FMediaInput from './forms/FMediaInput.vue'
import FColorPicker from './forms/FColorPicker.vue'
import FArrowPicker from './forms/FArrowPicker.vue'
import FChildren from "./forms/FChildren.vue";

const commonComponents = [
    UiLogo,
    UiCard,
    UiStatsCard,
    UiBadge,
    FSwitch,
    FInput,
    FLabel,
    FTable,
    FEditor,
    FButton,
    FUpload,
    FLocaleSelect,
    FMediaPicker,
    UiImagePresenter,
    UiSectionHeader,
    FImageInput,
    FModal,
    FConfirm,
    FCommentEditor,
    FSelect,
    UiDropdown,
    FSearchInput,
    FDatePicker,
    FConfirmDropdown,
    FTab,
    FTabs,
    FButtonList,
    FButtonSwitch,
    FButtonItem,
    FFileInput,
    FVideoInput,
    CreateModal,
    UiDashedBox,
    UiAvatar,
    HelpText,

    FMediaInput,
    FColorPicker,
    FArrowPicker,
    FChildren,
];

export default {
    install: (app) => {
        commonComponents.map((component) => {
            app.component(component.name, component);
        });
    },
};