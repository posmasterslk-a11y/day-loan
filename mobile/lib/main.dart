import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import 'providers/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/customers_screen.dart';
import 'screens/customer_register_screen.dart';
import 'screens/guarantors_screen.dart';
import 'screens/guarantor_register_screen.dart';
import 'screens/issue_loan_screen.dart';
import 'screens/collections_screen.dart';
import 'screens/payment_screen.dart';
import 'screens/arrears_screen.dart';
import 'screens/cashbook_screen.dart';
// Other screens will be imported here as we build them

void main() {
  runApp(const ProviderScope(child: MicrofinanceApp()));
}

class MicrofinanceApp extends ConsumerWidget {
  const MicrofinanceApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);

    final router = GoRouter(
      initialLocation: '/',
      redirect: (context, state) {
        final isLoggingIn = state.matchedLocation == '/';
        
        if (!authState.isAuthenticated && !isLoggingIn) return '/';
        if (authState.isAuthenticated && isLoggingIn) return '/dashboard';
        return null;
      },
      routes: [
        GoRoute(
          path: '/',
          builder: (context, state) => const LoginScreen(),
        ),
        GoRoute(
          path: '/dashboard',
          builder: (context, state) => const DashboardScreen(),
        ),
        GoRoute(
          path: '/customers',
          builder: (context, state) => const CustomersScreen(),
        ),
        GoRoute(
          path: '/customer-create',
          builder: (context, state) => const CustomerRegisterScreen(),
        ),
        GoRoute(
          path: '/guarantors',
          builder: (context, state) => const GuarantorsScreen(),
        ),
        GoRoute(
          path: '/guarantor-create',
          builder: (context, state) => const GuarantorRegisterScreen(),
        ),
        GoRoute(
          path: '/issue-loan',
          builder: (context, state) => const IssueLoanScreen(),
        ),
        GoRoute(
          path: '/collections',
          builder: (context, state) => const CollectionsScreen(),
        ),
        GoRoute(
          path: '/payment',
          builder: (context, state) {
            final data = state.extra as Map<String, dynamic>;
            return PaymentScreen(scheduleData: data);
          },
        ),
        GoRoute(
          path: '/arrears',
          builder: (context, state) => const ArrearsScreen(),
        ),
        GoRoute(
          path: '/cashbook',
          builder: (context, state) => const CashbookScreen(),
        ),
      ],
    );

    return MaterialApp.router(
      title: 'Micro Finance v1',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1E40AF)),
        useMaterial3: true,
        textTheme: GoogleFonts.interTextTheme(Theme.of(context).textTheme),
      ),
      routerConfig: router,
    );
  }
}
