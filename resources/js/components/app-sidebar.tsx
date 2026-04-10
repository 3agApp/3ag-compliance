import { Link } from '@inertiajs/react';
import {
    BoxIcon,
    FileText,
    LayoutGrid,
    Package,
    Tags,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import { index as productsIndex } from '@/routes/products';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as templatesIndex } from '@/routes/templates';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Suppliers',
        href: suppliersIndex(),
        icon: Package,
    },
    {
        title: 'Categories',
        href: categoriesIndex(),
        icon: Tags,
    },
    {
        title: 'Templates',
        href: templatesIndex(),
        icon: FileText,
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: BoxIcon,
    },
];



export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
