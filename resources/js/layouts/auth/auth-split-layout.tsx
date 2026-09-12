import { Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[47%_53%]">
            <aside className="hidden min-h-svh flex-col bg-zinc-950 px-[7vw] pt-[18vh] text-white lg:flex">
                <div className="max-w-xl">
                    <Link
                        href={home()}
                        className="text-xs font-semibold tracking-wide text-white uppercase"
                    >
                        Mertens AG
                    </Link>
                    <h1 className="mt-7 text-4xl leading-tight font-semibold tracking-tight text-balance xl:text-[2.5rem]">
                        Industrieanlagen im Blick.
                        <br />
                        Serviceeinsätze im Griff.
                    </h1>
                    <p className="mt-6 max-w-lg text-base leading-7 text-zinc-300">
                        Zentrale Verwaltung von Industrieanlagen, Standorten und
                        Wartung.
                    </p>
                </div>
            </aside>

            <main className="flex min-h-svh items-center justify-center p-6 sm:p-10 lg:p-16">
                <div className="w-full max-w-sm">
                    <Link
                        href={home()}
                        className="mb-6 inline-flex text-xs font-semibold tracking-wide uppercase lg:hidden"
                    >
                        Mertens AG
                    </Link>

                    <Card className="gap-0 rounded-xl py-0 shadow-xs">
                        <CardHeader className="gap-2 p-6 pb-0 sm:px-8 sm:pt-8">
                            <CardTitle className="text-lg">{title}</CardTitle>
                            <CardDescription>{description}</CardDescription>
                        </CardHeader>
                        <CardContent className="p-6 sm:p-8 sm:pt-6">
                            {children}
                        </CardContent>
                    </Card>
                </div>
            </main>
        </div>
    );
}
